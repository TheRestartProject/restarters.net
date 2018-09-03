@component('mail::message')
# Hi {{$name}},

A user has added data for devices to a recent event for you to review. Please log in to your dashboard to carry out this review.

@component('mail::button', ['url' => env('APP_URL', 'https://www.restarters.net')])
Go to Dashboard
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
