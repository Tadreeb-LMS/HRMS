<?php

namespace Modules\HrmsIntegrationModule\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\HrmsIntegrationModule\Services\HrmsWebhookService;

class HrmsEventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        // Map actual TadreebLMS events here
        // If event names are dynamic strings, we register via boot()
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        $webhookService = new HrmsWebhookService();

        // Listen for TadreebLMS core course events
        $events = $this->app['events'];

        // If TadreebLMS uses App\Events classes, listen to them. Assuming string names based on architecture logic:
        $events->listen('course.assigned', function ($course_id, $user_id) use ($webhookService) {
            $webhookService->dispatchEvent('course.assigned', ['course_id' => $course_id, 'user_id' => $user_id]);
        });

        $events->listen('course.progress', function ($course_id, $user_id, $progress) use ($webhookService) {
            $webhookService->dispatchEvent('course.progress', ['course_id' => $course_id, 'user_id' => $user_id, 'progress' => $progress]);
        });
        
        $events->listen('course.completed', function ($course_id, $user_id) use ($webhookService) {
            $webhookService->dispatchEvent('course.completed', ['course_id' => $course_id, 'user_id' => $user_id]);
        });
    }
}
