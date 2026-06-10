<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Contracts\Activity;

class ApiUser extends Model
{
  use LogsActivity;

  protected $fillable = [
    'name',
    'email',
    'domain',
    'ip',
    'password',
    'status',
  ];

  protected $hidden = [
    'password',
  ];


  public function getActivitylogOptions(): LogOptions
  {
    return LogOptions::defaults()
      ->logOnly(['name', 'email', 'domain', 'ip', 'status'])
      ->logOnlyDirty()
      ->useLogName('api_user');
  }

  public function tapActivity(Activity $activity, string $eventName)
  {
    $userName = $this->name ?? 'Unknown';

    switch ($eventName) {
      case 'created':
        $activity->log_name = 'api_user_created';
        $activity->description = "Created API User: {$userName}";
        break;

      case 'updated':
        $activity->log_name = 'api_user_updated';
        $activity->description = "Updated API User: {$userName}";
        break;

      case 'deleted':
        $activity->log_name = 'api_user_deleted';
        $activity->description = "Deleted API User: {$userName}";
        break;

      default:
        $activity->log_name = 'api_user';
        $activity->description = "Activity on API User: {$userName}";
    }
  }



}
