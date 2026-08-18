{{-- 公開範囲のバッジ（screens.md 3-3・3-5、mockup/channels.html） --}}
@if ($channel->isPublic())
    <span class="badge badge--public">公開</span>
@else
    <span class="badge badge--private">プライベート</span>
@endif
