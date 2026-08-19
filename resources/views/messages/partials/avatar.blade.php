{{-- アバター（screens.md 3-5「頭文字2字・人ごとに固定色」、mockup/channel-show.html）。
     色は design-guide.md §1 の4色を順に割り当てる（styles.css の .avatar / --b / --c / --d）。
     人ごとに固定にしたいので、その場の並び順ではなく利用者のIDから決める。 --}}
@php
    $avatarModifier = ['', ' avatar--b', ' avatar--c', ' avatar--d'][($user->id - 1) % 4];
@endphp
<span class="avatar{{ $avatarModifier }}">{{ mb_substr($user->name, 0, 2) }}</span>
