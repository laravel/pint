<input type="checkbox" @checked(
    $user->isActive() &&
    $user->hasVerifiedEmail()
) />

<option @selected(
    $selected === $country->code &&
    $country->isEnabled()
)>{{ $country->name }}</option>

<button @disabled(
    $form->isSubmitting() ||
    $form->hasErrors()
)>Save</button>

<input @readonly(
    $record->isLocked() &&
    ! $user->isAdministrator()
) />

<input @required(
    $field->isMandatory() &&
    ! $field->hasDefaultValue()
) />
