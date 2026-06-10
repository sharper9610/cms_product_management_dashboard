<?php

namespace App\Http\Controllers\pages;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponses;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityController extends Controller
{
    use HttpResponses;

    public function list()
    {

        $this->checkPageAccess('activity.view');

        $events = Activity::whereNotNull('event')
            ->where('event', '!=', '')
            ->distinct()
            ->orderBy('event')
            ->pluck('event')
            ->toArray();

        $data['events'] = $events;

        return view('content.pages.activities', $data);

    }



    public function index(Request $request)
    {
        $columns = [
            1 => 'log_name',
            2 => 'description',
            5 => 'created_at',
        ];

        $search = [
            1 => 'log_name',
            2 => 'description',
            5 => 'created_at',
            6 => 'event',
        ];

      $query = Activity::query()->whereNotNull('causer_id')
        ->where('causer_id', '!=', '');

        foreach ($search as $index => $field) {
            $searchValue = $request->input("columns.$index.search.value");

            if (! empty($searchValue)) {
                if ($field == 'created_at') {
                    if (strpos($searchValue, ' to ') !== false) {
                        $dates = explode(' to ', $searchValue);
                        if (count($dates) == 2) {
                            $startDate = Carbon::createFromFormat('Y-m-d', trim($dates[0]))->startOfDay();
                            $endDate = Carbon::createFromFormat('Y-m-d', trim($dates[1]))->endOfDay();

                            $query->whereBetween('created_at', [$startDate, $endDate]);
                        }
                    } else {
                        $date = Carbon::createFromFormat('Y-m-d', trim($searchValue))->startOfDay();
                        $query->whereDate('created_at', $date);
                    }
                } elseif ($field == 'description') {
                    $query->where(function ($q) use ($searchValue) {
                        $q->where('description', 'LIKE', "%{$searchValue}%");
                    });
                } elseif ($field == 'event') {
                    $query->where(function ($q) use ($searchValue) {
                        $q->where('event', 'LIKE', "%{$searchValue}%");
                    });
                } else {
                    $query->where(function ($q) use ($searchValue) {
                        $q->where('log_name', 'LIKE', "%{$searchValue}%");
                    });

                    //          $query->where($field, 'LIKE', "%{$searchValue}%");
                }
            }
        }

        $totalData = $query->count();
        $totalFiltered = $totalData;

        $limit = $request->input('length', 10);
        $start = $request->input('start', 0);
        $order = $columns[$request->input('order.0.column')] ?? 'created_at';
        $dir = $request->input('order.0.dir') ?? 'desc';

        $orders = $query->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir)
            ->with('causer:id,name,email')
            ->get();

        $data = [];

        foreach ($orders as $user) {
            $nestedData['id'] = $user->id;
            $nestedData['fake_id'] = $user->id;
            $nestedData['event'] = $user->event ?? '';
            $nestedData['log_name'] = $user->log_name ?? '';
            $nestedData['description'] = $user->description ?? '';
            $nestedData['properties'] = $user->properties;

          if (empty($user->causer_id)) {
            $nestedData['causer_name']  = 'Cron User';
            $nestedData['causer_email'] = '';
          } else {
            $nestedData['causer_name']  = optional($user->causer)->name ?? 'Not found';
            $nestedData['causer_email'] = optional($user->causer)->email ?? '';
          }

            $nestedData['causer_id'] = $user->causer_id ?? '';
            $nestedData['created_at'] = $user->created_at->format('Y-M-d H:i:s') ?? '';
            $data[] = $nestedData;
        }

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => intval($totalData),
            'recordsFiltered' => intval($totalFiltered),
            'code' => 200,
            'data' => $data,
        ]);
    }

    public function show($id)
    {
        $user = Activity::with(['causer'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }
}
