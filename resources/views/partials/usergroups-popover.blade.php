<ul style='list-style-type:none; margin:0; padding:0'>
@foreach($u->groups as $n => $g)
    <li>
        {{ $g->name }}
    </li>
@endforeach
</ul>
