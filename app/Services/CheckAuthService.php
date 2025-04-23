<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\Helpers\Fixometer;
use App\Models\User;
use Cookie;
use Illuminate\Http\Resources\Json\JsonResource;
use Lang;

class CheckAuthService extends JsonResource
{
    /**
     * @var object
     */
    private $menu;

    /**
     * @var bool
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
     * @var bool
     */
    private $is_admin = null;

    /**
     * @var bool
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
    }

    private function handle($email)
    {
        $this->user = User::where('email', $email)->first();

        if (! $this->user) {
            return false;
        }

        $this->authenticated = true;
        $this->edit_profile_link = $this->edit_profile_link.$this->user->id;

        if ($this->is_host || $this->is_admin) {
            $this->menu->get('reporting')->put('header', 'Reporting');

            $this->menu->get('reporting')->put(Lang::get('general.party_reporting'), url('search'));

            $this->menu->get('reporting')->put('reporting_spacer', 'spacer');
        }
    }

    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'authenticated' => $this->authenticated,
            'edit_profile_link' => $this->edit_profile_link,
            'is_admin' => $this->is_admin,
            'menu' => $this->menu->toArray(),
        ];
    }
}
