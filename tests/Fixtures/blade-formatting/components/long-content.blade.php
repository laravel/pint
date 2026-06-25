<div>
@if ($this->pendingInvitations->isNotEmpty())
<flux:modal name="pending-invitations" wire:model="showPendingInvitationsModal" focusable class="max-w-lg">
        <div data-test="pending-invitations-modal" class="space-y-6">
  <div>
        <flux:heading size="lg">{{__('Pending team invitations')}}</flux:heading>
            <flux:subheading>{{    __('Accept or decline the teams you have been invited to join.')    }}</flux:subheading>
                </div>
        </div>
    </flux:modal>
        @endif
</div>
