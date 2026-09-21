{{ config('mail.from.name') ?: config('app.name') }} — {{ $subject }}

@if($recipientName)Dear {{ $recipientName }},

@endif{!! $body !!}

---
Oceanovia
{{ config('mail.from.address') }}
© {{ date('Y') }} Oceanovia. All rights reserved.