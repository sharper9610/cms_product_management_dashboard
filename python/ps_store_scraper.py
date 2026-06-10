import json
import re
import requests
from bs4 import BeautifulSoup
from fake_useragent import UserAgent
from datetime import datetime
import dateutil.parser
import sys

ua = UserAgent(browsers=["chrome", "firefox", "edge"], min_percentage=1.5)

HEADERS = {
    "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
    "Accept-Language": "pt-BR,pt;q=0.9,en;q=0.8",
    "Accept-Encoding": "gzip, deflate, br",
    "Connection": "keep-alive",
    "Upgrade-Insecure-Requests": "1",
    "Sec-Fetch-Dest": "document",
    "Sec-Fetch-Mode": "navigate",
    "Sec-Fetch-Site": "none",
}


def parse_offer_data(html: str):
    """Extract pricing fields from PS Store HTML."""
    soup = BeautifulSoup(html, "lxml")

    # Final price
    final_price = None
    final_el = soup.find("span", {"data-qa": "mfeCtaMain#offer0#finalPrice"})
    if final_el:
        final_price = final_el.get_text(strip=True)

    # Original price
    original_price = None
    orig_el = soup.find("span", {"data-qa": "mfeCtaMain#offer0#originalPrice"})
    if orig_el:
        original_price = orig_el.get_text(strip=True)

    # Discount percent
    discount_percent = None
    disc_el = soup.find("span", {"data-qa": "mfeCtaMain#offer0#discountInfo"})
    if disc_el:
        discount_percent = disc_el.get_text(strip=True)

    # Deadline
    discount_deadline = None
    discount_deadline_unix = None
    deadline_el = soup.find("span", {"data-qa": "mfeCtaMain#offer0#discountDescriptor"})

    if deadline_el:
        discount_deadline = deadline_el.get_text(strip=True)

        match = re.search(r"on (.+)", discount_deadline)
        if match:
            try:
                dt = dateutil.parser.parse(match.group(1))
                discount_deadline_unix = int(dt.timestamp())
            except:
                pass

    # Lowest price in last 30 days
    lowest_recent_price = None
    low_el = soup.find("span", {"data-qa": "mfeCtaMain#offer0#lowestRecentPrice"})
    if low_el:
        lowest_recent_price = low_el.get_text(strip=True)

    return {
        "final_price": final_price,
        "original_price": original_price,
        "discount_percent": discount_percent,
        "discount_deadline": discount_deadline,
        "discount_deadline_unix": discount_deadline_unix,
        "lowest_recent_price": lowest_recent_price,
    }


def scrape_ps_store(url: str):
    """Scrape title + pricing from any PlayStation Store URL."""

    session = requests.Session()
    session.headers.update(HEADERS)
    session.headers["User-Agent"] = ua.random

    try:
        r = session.get(url, timeout=20)

        if r.status_code != 200:
            return {"error": f"HTTP {r.status_code}"}

        if any(x in r.text.lower() for x in ["cloudflare", "blocked", "checking your browser"]):
            return {"error": "BLOCKED_BY_CLOUDFLARE"}

        html = r.text

        # Find embedded JSON for title
        json_match = re.search(
            r'<script id="env:[^"]+" type="application/json">({.*?})</script>',
            html,
            re.DOTALL
        )

        title = None
        if json_match:
            try:
                data = json.loads(json_match.group(1))
                cache = data.get("cache", {})
                for key, val in cache.items():
                    if key.startswith("Product:") and "name" in val:
                        title = val["name"]
                        break
            except:
                pass

        price_info = parse_offer_data(html)

        return {
            "url": url,
            "title": title,
            "price_data": price_info,
        }

    except Exception as e:
        return {"error": str(e)}


# -----------------------------------------------------------
# MAIN TEST FUNCTION
# -----------------------------------------------------------
def main():
    if len(sys.argv) < 2:
        print("Usage:")
        print("   python ps_store_scraper.py <ps_store_url>")
        print("\nExample:")
        print("   python ps_store_scraper.py https://store.playstation.com/pt-br/concept/10001131")
        return

    url = sys.argv[1]
    print(f"\nScraping: {url}\n")

    result = scrape_ps_store(url)

    if "error" in result:
        print("ERROR:", result["error"])
        return

    print("=== RESULT ===")
    print("Title:", result["title"])

    price = result["price_data"]
    print("Final Price:", price["final_price"])
    print("Original Price:", price["original_price"])
    print("Discount:", price["discount_percent"])
    print("Ends:", price["discount_deadline"])
    print("Unix Timestamp:", price["discount_deadline_unix"])
    print("Lowest 30d:", price["lowest_recent_price"])


if __name__ == "__main__":
    import sys
    import json

    if len(sys.argv) < 2:
        print(json.dumps({"error": "Missing URL parameter"}))
        sys.exit(1)

    url = sys.argv[1]

    result = scrape_ps_store(url)

    # Always output JSON ONLY
    print(json.dumps(result, ensure_ascii=False))
    sys.exit(0)
