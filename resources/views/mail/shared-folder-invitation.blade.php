<x-mail::message>
# Shared folder invitation

{{ $ownerName }} invited you to join **{{ $folderName }}** on StudentVault.

Sign in with the email address that received this invitation to accept or decline it. This email does not grant access by itself.

<x-mail::button :url="route('shared-folders.invitations.index')">
View invitation
</x-mail::button>

Thanks,<br>
StudentVault
</x-mail::message>
