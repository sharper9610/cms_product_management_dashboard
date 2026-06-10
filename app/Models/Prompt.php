<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
class Prompt extends Model
{
  use LogsActivity;
  
  protected $fillable = [
      'name',
      'description',
      'template',
      'template_pt',
      'template_es',
      'is_active',
  ];

  public function getActivitylogOptions(): LogOptions
  {
    return LogOptions::defaults()
      ->logOnly(['name', 'description', 'template'])
      ->logOnlyDirty()
      ->useLogName('prompt');
  }

  public function tapActivity(Activity $activity, string $eventName)
  {


    $promptId = $this->id;

    switch ($eventName) {
      case 'created':
        $activity->log_name = 'prompt_created';
        $activity->description = "Created prompt with ID: {$promptId}";
        break;

      case 'updated':
        $activity->log_name = 'prompt_updated';
        $activity->description = "Updated prompt with ID: {$promptId}";
        break;

      case 'deleted':
        $activity->log_name = 'prompt_deleted';
        $activity->description = "Deleted prompt with ID: {$promptId}";
        break;

      default:
        $activity->log_name = 'prompt';
        $activity->description = "Activity on prompt with ID: {$promptId}";
    }
  }
}
