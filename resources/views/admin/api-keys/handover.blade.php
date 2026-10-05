{{--
    Teks serah-terima API key ke tim lain — dirakit di ApiKeyController::handoverText().

    Sengaja teks polos berbahasa Inggris, bukan lewat file lang: yang menerima
    biasanya tim di luar organisasi, dan isinya mau bisa diubah langsung di sini
    tanpa menyentuh tujuh file terjemahan. Blok "Authorized Services" dan
    "Allowed Referers" sudah jadi teks di PHP supaya barisnya tidak diacak Blade.
--}}
AWS Location Service API Key

Provider        : {{ $provider }}
Environment     : {{ $environment }}
Region          : {{ $region }}
API Key Name    : {{ $keyName }}
Expiration Date : {{ $expiry }}

API Key
{{ $value }}

Authorized Services
{!! $services !!}
{!! $referers !!}Notes
- This API key is intended for accessing AWS Location Service (Grab Maps) in the {{ \Illuminate\Support\Str::lower($environment) }} environment.
- Please keep the API key confidential and do not share it with unauthorized parties.
- Do not expose the API key in public repositories or client-side applications unless appropriate restrictions are in place.
- If the API key is suspected to be compromised, please contact us immediately so it can be rotated.
