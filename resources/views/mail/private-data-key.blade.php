<x-mail::message>
# Access request approved

{{ $ownerName }} approved your request for **{{ $resourceDescription }}** on StudentVault.

The access key below is encrypted with your StudentVault public key (RSA-OAEP, SHA-256). Only your account can decrypt it, so it is safe to keep. Do not edit it.

<x-mail::panel>
{{ $encryptedKey }}
</x-mail::panel>

Sign in to StudentVault to open the approved item. This email does not grant access by itself, and the owner can revoke access at any time.

<x-mail::button :url="route('private-access.index')">
View your requests
</x-mail::button>

Thanks,<br>
StudentVault
</x-mail::message>
