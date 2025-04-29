<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Notifications\NewGroupWithinRadius;
use App\Models\Party;
use App\Models\Role;
use App\Models\User;
use DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BasicTest extends TestCase
{
    public function testNotificationsPage(): void {
        // Create host user with fast login and required language field
        $host = User::factory()->host()->create([
            'language' => 'en',  // Set language to prevent preferredLocale() null error
        ]);
        
        // Create admin and group more efficiently
        $admin = $this->fastLoginAsTestUser(Role::ADMINISTRATOR);
        
        // Create group directly instead of using createGroup helper
        $group = Group::factory()->create([
            'approved' => true,
        ]);
        
        // Send real notification - don't fake it as we need the database record
        $data = [
            'group_name' => $group->name,
            'group_url' => url('/group/view/'.$group->idgroups),
        ];
        
        $host->notify(new NewGroupWithinRadius($data));
        
        // Process the notification queue if needed
        $this->processQueuedNotifications();

        // Login as host to check notifications page
        $this->actingAs($host);
        
        // Get notifications page
        $response = $this->get('/profile/notifications');
        
        // Check page loaded successfully
        $response->assertStatus(200);
        
        // Check notification message appears
        $response->assertSee(__('notifications.new_group_title'));
        
        // Extract the markAsRead URL
        if (preg_match('/.*(markAsRead\/.+?)".*/', $response->getContent(), $matches)) {
            // Visit the markAsRead URL
            $markResponse = $this->get($matches[1]);
            
            // Should redirect after marking as read
            $markResponse->assertStatus(302);
        } else {
            $this->fail('Could not find markAsRead URL in notifications page');
        }
    }
}
