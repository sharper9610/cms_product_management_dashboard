@php
  use Illuminate\Support\Facades\Vite;

  $menuCollapsed = $configData['menuCollapsed'] === 'layout-menu-collapsed' ? json_encode(true) : false;

  // Get skin value directly from the config, keeping it as numeric if applicable
  $skin = $configData['skins'] ?? 0;

  // If we have a skin name from cookie or other source, use that instead
  $skinName = $configData['skinName'] ?? '';

  // Use either the skin name or numeric ID, prioritizing the name if available
  $defaultSkin = $skinName ?: $skin;

  // Define layout type and cookie naming
  $isAdminLayout = !str_contains($configData['layout'] ?? '', 'front');
  $primaryColorCookieName = $isAdminLayout ? 'admin-primaryColor' : 'front-primaryColor';

  // Get primary color - first from cookie, then from config
  $primaryColor = isset($_COOKIE[$primaryColorCookieName])
      ? $_COOKIE[$primaryColorCookieName]
      : $configData['color'] ?? null;
@endphp
<!-- laravel style -->
@vite(['resources/assets/vendor/js/helpers.js'])
<!-- beautify ignore:start -->
@if ($configData['hasCustomizer'])
<!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
  <!--? Template customizer: To hide customizer set displayCustomizer value false in config.js.  -->
  @vite(['resources/assets/vendor/js/template-customizer.js'])
@endif

  <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
  @vite(['resources/assets/js/config.js'])

<script>
  var userPermissions = @json(auth()->check() ? auth()->user()->getAllPermissions()->pluck('name') : []);





  //
  // function showToast(message, action, type = 'bg-success', duration = 3000) {
  //   const toastPlacementExample = document.querySelector('.toast-placement-ex');
  //   const toastBody = document.getElementById('toast-body');
  //   const toastTitle = document.getElementById('toast-title');
  //
  //   toastBody.innerHTML = message;
  //   toastTitle.innerHTML = action;
  //
  //   // Remove existing background classes before adding the new one
  //   toastPlacementExample.classList.remove('bg-success', 'bg-warning', 'bg-danger');
  //   toastPlacementExample.classList.add(type);
  //
  //   // Remove old placement and apply new
  //   const selectedPlacement = ['top-0', 'end-0'];
  //   DOMTokenList.prototype.remove.apply(toastPlacementExample.classList, selectedPlacement);
  //   DOMTokenList.prototype.add.apply(toastPlacementExample.classList, selectedPlacement);
  //
  //   // Initialize toast with delay
  //   const toastPlacement = new bootstrap.Toast(toastPlacementExample, {
  //     delay: duration,  // Duration in milliseconds
  //     autohide: true    // Ensure toast auto-hides
  //   });
  //
  //   toastPlacement.show();
  //
  //   // Optional: Dispose after duration to free memory
  //   setTimeout(() => {
  //     toastDispose(toastPlacement, type);
  //   }, duration + 100); // Delay + small buffer
  // }
  //
  //
  // function toastDispose(toast, type = 'bg-success') {
  //   const toastPlacementExample = document.querySelector('.toast-placement-ex');
  //   if (toast && toast._element !== null) {
  //     if (toastPlacementExample) {
  //       var selectedType = type;
  //       var selectedPlacement = ['top-0', 'end-0'];
  //       toastPlacementExample.classList.remove(selectedType);
  //       DOMTokenList.prototype.remove.apply(toastPlacementExample.classList, selectedPlacement);
  //     }
  //     // if (toastAnimationExample) {
  //     //   toastAnimationExample.classList.remove(selectedType, selectedAnimation);
  //     // }
  //     toast.dispose();
  //   }
  // }


  function showToast(message, title = 'Bootstrap', typeClass = 'text-success', duration = 3000) {
    const toastPlacementExample = document.querySelector('.toast-placement-ex');
    let selectedType, selectedPlacement, toastPlacement;

    function toastDispose(toast) {
      if (toast && toast._element !== null) {
        if (toastPlacementExample) {
          toastPlacementExample.querySelectorAll('i[class*="ri-"]').forEach(function (element) {
            element.classList.remove(selectedType);
          });
          DOMTokenList.prototype.remove.apply(toastPlacementExample.classList, selectedPlacement);
        }
        toast.dispose();
      }
    }

    if (toastPlacement) {
      toastDispose(toastPlacement);
    }

    // Apply classes
    selectedType = typeClass;
    selectedPlacement = 'top-0 end-0'.split(' ');

    toastPlacementExample.querySelectorAll('i[class*="ri-"]').forEach(function (element) {
      element.classList.add(selectedType);
    });
    DOMTokenList.prototype.add.apply(toastPlacementExample.classList, selectedPlacement);

    // Set content
    toastPlacementExample.querySelector('.me-auto').innerText = title;
    toastPlacementExample.querySelector('.toast-body').innerText = message;

    // Show toast with custom duration
    toastPlacement = new bootstrap.Toast(toastPlacementExample, { delay: duration });
    toastPlacement.show();
  }






</script>

@if ($configData['hasCustomizer'])
<script type="module">
  document.addEventListener('DOMContentLoaded', function() {
    // Initialize template customizer after DOM is loaded
    if (window.TemplateCustomizer) {
      try {
        // Get the skin currently applied to the document
        const appliedSkin = document.documentElement.getAttribute('data-skin') || "{{ $defaultSkin }}";

        window.templateCustomizer = new TemplateCustomizer({
          defaultTextDir: "{{ $configData['textDirection'] }}",
          @if ($primaryColor)
            defaultPrimaryColor: "{{ $primaryColor }}",
          @endif
          defaultTheme: "{{ $configData['themeOpt'] }}",
          defaultSkin: appliedSkin,
          defaultSemiDark: {{ $configData['semiDark'] ? 'true' : 'false' }},
          defaultShowDropdownOnHover: "{{ $configData['showDropdownOnHover'] }}",
          displayCustomizer: "{{ $configData['displayCustomizer'] }}",
          lang: '{{ app()->getLocale() }}',
          'controls': <?php echo json_encode($configData['customizerControls']); ?>,
        });

        // Ensure color is applied on page load
        @if ($primaryColor)
          if (window.Helpers && typeof window.Helpers.setColor === 'function') {
            window.Helpers.setColor("{{ $primaryColor }}", true);
          }
        @endif
      } catch (error) {
        console.warn('Template customizer initialization error:', error);
      }
    }
  });
</script>
<script>
  document.addEventListener("DOMContentLoaded", function () {
    const noticeLinks = document.querySelectorAll(".notice-link");
    const modalTitle = document.getElementById("noticeTitle");
    const modalDesc = document.getElementById("noticeDescription");

    noticeLinks.forEach(link => {
      link.addEventListener("click", function () {
        modalTitle.textContent = this.getAttribute("data-title");
        modalDesc.textContent = this.getAttribute("data-description");
      });
    });
  });
</script>
@endif
