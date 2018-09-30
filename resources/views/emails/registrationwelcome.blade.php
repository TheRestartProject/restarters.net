@component('mail::message')

![welcome]({{asset(env('APP_URL') . '/images/registration-welcome-image.jpg')}})

# Welcome to the Restarters community


Hi {{ $firstName }},

Welcome to Restarters.net, a space for people around the world who help others fix their electronics at community events. We’re glad to have you with us!

Now you’ve signed up, here are some ways you can get involved:

## Come and say hi

Head to our [Discussion Forum]({{ env('DISCOURSE_URL') }}) where you can meet people from our global community of electronics repairers and event organisers. This is also the perfect place to get help with running your own events and chat about all things repair.

## Go to a repair event

Check your [dashboard]({{ env('APP_URL') }}/dashboard) to see which events are coming up near you. Why not RSVP to one and come along? These are great chances to meet some fellow Restarters and find out how it all works.

## Join or start your own local group

The [Groups page]({{ env('APP_URL') }}/group) will show you the existing groups nearest to you. It’s a good idea to join your nearest group so you can see when they run events more easily. 

Nothing happening in your area yet? We can provide the tools you need to get your own local event off the ground. Just create a group from the [Groups page]({{ env('APP_URL') }}/group) or ask for a hand in the Discussion Forum to get started.

If you have any questions, need a hand or just want to chat, feel free to drop us a line over in the [Help & Feedback category]({{ env('DISCOURSE_URL') }}/c/help) in the Discussion Forum.

Look forward to seeing you around!

James @ The Restart Team

@endcomponent


