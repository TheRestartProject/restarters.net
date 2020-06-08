<?php

namespace App\Services;

use App\User;
use Cookie;
use FixometerHelper;
use Illuminate\Http\Resources\Json\JsonResource;
use Lang;

class CheckAuthService extends JsonResource
{
    /**
     * @var object
     */
    private $menu;

    /**
     * @var boolean
     */
    private $authenticated = null;

    /**
     * @var object
     */
    private $user;

    /**
     * @var string
     */
    private $edit_profile_link = '';

    /**
     * @var boolean
     */
    private $is_admin = null;

    /**
     * @var boolean
     */
    private $is_host = null;

    public function __construct()
    {
        $this->edit_profile_link = env('APP_URL').'/profile/edit/';

        $this->menu = collect([
            'general' => collect([]),
            'reporting' => collect([]),
            'user' => collect([]),
        ]);

        if (Cookie::get('authenticated')) {
            $this->handle(Cookie::get('authenticated'));
        }

        $this->menu->get('general')->put(Lang::get('general.about_page'), Lang::get('general.about_page_url'));
        $this->menu->get('general')->put(Lang::get('general.guidelines_page'), Lang::get('general.guidelines_page_url'));
        $this->menu->get('general')->put(Lang::get('general.privacy_page'), Lang::get('general.privacy_page_url'));
        $this->menu->get('general')->put(Lang::get('general.menu_help_feedback'), Lang::get('general.help_feedback_url'));
        $this->menu->get('general')->put(Lang::get('general.menu_help_feedback'), Lang::get('general.help_feedback_url'));
        $this->menu->get('general')->put(Lang::get('general.menu_faq'), Lang::get('general.faq_url'));
        $this->menu->get('general')->put(Lang::get('general.therestartproject'), Lang::get('general.restartproject_url'));

        //$this->populateUserDropdownItems($user);
    }

    private function handle($email)
    {
        $this->user = User::where('email', $email)->first();

        if ( ! $this->user) {
            return false;
        }

        $this->authenticated = true;
        //$this->is_admin = $this->user->getUserFromDiscourse()['user']['admin'];
        //$this->is_host = $this->user->getUserFromDiscourse()['user']['moderator'];
        $this->edit_profile_link = $this->edit_profile_link.$this->user->id;

        if ($this->is_host || $this->is_admin) {
            $this->menu->get('reporting')->put('header', 'Reporting');

            if ($this->is_admin) {
                $this->menu->get('reporting')->put(Lang::get('general.time_reporting'), url('reporting/time-volunteered?a'));
            }

            $this->menu->get('reporting')->put(Lang::get('general.party_reporting'), url('search'));

            $this->menu->get('reporting')->put('reporting_spacer', 'spacer');
        }
    }

    /**
      * Transform the resource into an array.
      *
      * @param  \Illuminate\Http\Request  $request
      * @return array
      */
    public function toArray($request)
    {
        return [
            'authenticated' => $this->authenticated,
            'edit_profile_link' => $this->edit_profile_link,
            'is_admin' => $this->is_admin,
            'menu' => $this->menu->toArray(),
        ];
    }

    private function populateUserDropdownItems($user)
    {
        $user_menu = $this->menu->get('user');

        $user_menu->put(Lang::get('general.profile'), url('profile/edit/'.$user->id));

        if (FixometerHelper::hasRole($user, 'Administrator')) {
            $user_menu->put('profile_spacer', 'spacer');
            $user_menu->put('header', 'Administrator');
            $user_menu->put('Brands', route('brands'));
            $user_menu->put('Skills', route('skills'));
            $user_menu->put('Group tags', route('tags'));
            $user_menu->put('Categories', route('category'));
            $user_menu->put('Users', route('users'));
            $user_menu->put('Roles', route('roles'));

            if (FixometerHelper::hasPermission('verify-translation-access', $user)) {
                $user_menu->put('Translations', url('translations'));
            }
        }

        if (FixometerHelper::hasPermission('repair-directory', $user)) {
            $user_menu->put('Repair Directory', config('restarters.repairdirectory.base_url').'/admin');
        }

        $user_menu->put('logout_spacer', 'spacer');

        $user_menu->put(Lang::get('general.logout'), url('logout'));
    }
}
