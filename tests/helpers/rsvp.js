// Filling in the RSVP form. Every field is needed (Mark, 12 Nov 2026), and the
// partner question only shows to someone coming, so specs that send a reply
// fill it the way a guest has to.

// The invitee's own fields. `scope` is the page, or the panel holding the form.
export async function fillInvitee(scope, { name, email, phone = '7700 900123', dietary = 'none', allergies = 'None' }) {
  await scope.locator('#bf-name').fill(name);
  await scope.locator('#bf-phone').fill(phone);
  await scope.locator('#bf-email').fill(email);
  await scope.locator('#bf-dietary').selectOption(dietary);
  await scope.locator('#bf-allergies').fill(allergies);
}

export const attendingRadio = (scope, answer) => scope.locator(`input[name="attending"][value="${answer}"]`);
export const partnerRadio = (scope, answer) => scope.locator(`input[name="with_guest"][value="${answer}"]`);

// Coming, on their own.
export async function comingAlone(scope) {
  await attendingRadio(scope, 'yes').check();
  await partnerRadio(scope, 'no').check();
}
