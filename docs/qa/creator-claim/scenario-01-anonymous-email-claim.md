# QA Scenario 01 — Anonymous Email Claim (First-Time Flow)

**Target:** `http://toppingafrica.test`
**Purpose:** Verify that an anonymous visitor can claim a creator profile through the email-based flow, submit edits, and see the "Complete your account" banner after save. This is the most common path and exercises the full claim stack end-to-end.

**Expected duration:** ~2–3 minutes of agent activity.

---

## Preconditions

Before starting, the following should already be true on the target environment. If any are missing, the test can't run and you should report what you found and stop.

1. The site at `http://toppingafrica.test` is reachable and returns HTTP 200 on the home page.
2. A creator exists at `http://toppingafrica.test/creators/amina-swahili-style` with the visible name **"Amina Swahili Style"**. If that creator has already been claimed (you'll see a gray "Claimed" badge instead of a "Claim Profile" button in the top-right of the profile card), stop and report — this scenario needs an unclaimed creator and the default test subject has already been consumed. Pick any other creator on the `/creators` directory page that shows a "Claim Profile" button and substitute its slug in the URL, or report back for a new test subject.
3. The site's queue worker is running (otherwise the claim invitation email will never be dispatched). Indirect way to verify: after submitting the claim form in Step 3 below, the maildrop.cc inbox should receive the email within 30 seconds. If it doesn't arrive within 2 minutes, the queue worker is probably not running.

You do NOT need to be logged in for this scenario. If you are logged in as any user from a previous test, log out first before starting.

---

## Test data

- **Test email address:** Use a fresh disposable maildrop.cc address each run. Generate one by taking the current date and adding a random suffix, e.g. `qa-claim-2026-04-08-abc123@maildrop.cc`. Record the exact address you use — you'll need it to check the inbox later.
- **Creator slug under test:** `amina-swahili-style`
- **Creator profile URL:** `http://toppingafrica.test/creators/amina-swahili-style`

---

## Steps

### Step 1 — Navigate to the creator profile page

Open a browser and go to:

```
http://toppingafrica.test/creators/amina-swahili-style
```

**Expected:**
- Page loads successfully (HTTP 200).
- You see the creator's name "Amina Swahili Style" prominently displayed.
- In the top-right corner of the profile card, there is a visible **"Claim Profile"** button (pill-shaped, light gray background). If you instead see a "Claimed" badge (disabled, gray, with a checkmark) or an "Edit your profile" button (black, with a pencil icon), stop and report — the creator isn't in the expected unclaimed state.
- You should NOT see any authenticated user indicator (no avatar in the site header — you should see a "LOGIN" button instead). If you're logged in from a previous test, log out first and retry this step.

**Report:** the screenshot or description of the profile card and confirm the "Claim Profile" button is visible.

---

### Step 2 — Click "Claim Profile" and submit the claim request

Click the **"Claim Profile"** button in the top-right of the profile card.

**Expected:**
- A modal dialog opens with the heading **"Claim this profile"** and subtitle text that begins with "Is this you? Enter your email...".
- Inside the modal there is a single email input field and a button labeled **"Send claim link"**.

Enter the test email address you generated earlier (e.g. `qa-claim-2026-04-08-abc123@maildrop.cc`) into the email field.

Click **"Send claim link"**.

**Expected:**
- The form submits without any visible error messages.
- The modal either closes or is replaced with a success message containing the text **"A claim link has been sent to your email. Check your inbox!"**
- If you see a red error message saying "reCAPTCHA verification failed" or "Please enter a valid email address", stop and report. (reCAPTCHA is enabled in production but may be disabled on local; if it's enabled and failing here, the test cannot proceed.)

**Report:** confirm the success message appeared and record the exact time you clicked Send. You'll need the time to correlate with the email delivery in the next step.

---

### Step 3 — Retrieve the claim link from the email inbox

This is the step most likely to fail depending on browser-agent capabilities. Try the primary method first; if it doesn't work, fall back to the alternative.

**Primary method — check maildrop.cc inbox:**

Navigate to:

```
https://maildrop.cc/inbox/<local-part-of-your-test-email>/
```

Where `<local-part-of-your-test-email>` is the portion of your test address BEFORE the `@` sign. For example, if your test email was `qa-claim-2026-04-08-abc123@maildrop.cc`, navigate to:

```
https://maildrop.cc/inbox/qa-claim-2026-04-08-abc123/
```

**Expected:**
- The maildrop.cc inbox page loads.
- Within 30 seconds, a new email appears with subject **"Claim your Topping Africa Creator Profile"** from `noreply@toppingafrica.com`.
- Click to open the email.
- The email body starts with **"Hi there,"** (because maildrop addresses don't have User accounts, the personalized greeting falls back to "Hi there,").
- The body contains the text **"You've requested to claim"** followed by the creator's name.
- There is a black **"Claim Your Profile"** button in the email.

Click the **"Claim Your Profile"** button in the email body.

**Expected:** a new tab or the current tab navigates to a URL of the form:

```
http://toppingafrica.test/creators/claim/<some-uuid-token>
```

Proceed to Step 4.

**Fallback method — if you cannot access maildrop.cc or the email doesn't arrive:**

Skip the email entirely and grab the claim token directly from the database.

Open a terminal (or whatever code-execution surface you have) and run:

```bash
cd /c/laragon/www/toppingafrica
php artisan tinker --execute="echo 'http://toppingafrica.test/creators/claim/' . \App\Models\Creator::where('slug', 'amina-swahili-style')->value('claim_token') . PHP_EOL;"
```

This prints the exact magic-link URL a real claimant would click. Open that URL in the browser and proceed to Step 4.

If neither the primary nor fallback method is available to you, stop and report which ones you tried and why they failed.

---

### Step 4 — Verify the claim edit form loads

After clicking the link (either from the email or via the fallback URL), the browser should land on the claim edit form.

**Expected:**
- URL starts with `http://toppingafrica.test/creators/claim/` followed by a UUID-shaped token.
- Page title reads **"Claim Your Profile"** at the top.
- Subtitle: **"Hi Amina Swahili Style! Update your profile below. Changes go live immediately and our team may review them."**
- The form contains the following fields visible on the page:
  - A **Profile Photo** section with a current avatar and a "Choose File" input
  - A **Bio** textarea already populated with Amina's existing bio text
  - A **Social Links** section with at least one social link row (platform dropdown, URL input, handle input)
  - A **"Save Changes"** button at the bottom

**Report:** confirm the form loaded and the bio textarea is pre-filled (it shouldn't be empty).

---

### Step 5 — Submit an edit

Modify the bio textarea. Append the following text to the existing bio content (don't replace what's already there — just add to the end):

```
 [QA test edit — please revert]
```

(Note the leading space, so it joins cleanly onto the existing bio without smashing into the last word.)

Do NOT touch the photo or social links.

Click the **"Save Changes"** button.

**Expected:**
- The page redirects back to the same claim edit URL (no navigation to a different route).
- A green success banner appears near the top with the text **"Your changes are live. Our team may review them shortly."**
- Below the success banner, a second banner appears with the heading **"Want to edit this profile anytime?"** and a black button labeled **"Complete your account →"**.
- The bio textarea still shows your edited text (not reverted).

**Report:** both banners are visible. If only the success banner shows but the "Complete your account" banner is missing, stop and report — the `offer_register` flash session is not being set and that's a regression from the refactor we did earlier.

---

### Step 6 — Verify the edit is public

This step verifies the edit actually reached the live profile (since we explicitly removed the "submitted for review" queue in favor of live edits).

Open a new tab (don't navigate away from the claim form) and go to:

```
http://toppingafrica.test/creators/amina-swahili-style
```

**Expected:**
- The creator's bio on the public profile page now ends with **"[QA test edit — please revert]"**.
- The top-right corner of the profile card still shows "Claim Profile" (not "Claimed"), because the claim flow does NOT flip the status to claimed until either an admin explicitly approves OR the user registers an account. This is correct behavior.

**Report:** confirm the bio edit is visible on the public page.

---

## Teardown (cleanup after the test)

**Important:** if you don't run teardown, the test creator is left in a dirty state for the next test run.

Run these commands in a terminal to restore the creator to its original state:

```bash
cd /c/laragon/www/toppingafrica
php artisan tinker --execute="
\$c = \App\Models\Creator::where('slug', 'amina-swahili-style')->first();
\$c->bio = str_replace(' [QA test edit — please revert]', '', \$c->bio);
\$c->claim_token = null;
\$c->claim_token_expires_at = null;
\$c->claimed_by_email = null;
\$c->pending_claim_edit = false;
\$c->save();
echo 'teardown complete' . PHP_EOL;
"
```

This:
- Strips the `[QA test edit — please revert]` suffix from the bio
- Clears the claim token so the creator is eligible for a fresh claim on the next run
- Clears the `claimed_by_email` field
- Clears the `pending_claim_edit` flag so the creator doesn't show up in the admin's Pending Edits tab

It does NOT delete any maildrop.cc emails or any queued / sent mail jobs — those age out naturally.

---

## Success criteria (summary)

The scenario PASSES if all of the following are true:

- [ ] Step 1: Profile page loaded, "Claim Profile" button visible, no authenticated user
- [ ] Step 2: Claim request submitted successfully, success message shown
- [ ] Step 3: Email received in maildrop.cc inbox OR fallback URL retrieved from DB
- [ ] Step 4: Claim edit form loaded with bio pre-filled
- [ ] Step 5: Edit saved, both success banner AND "Complete your account" banner visible
- [ ] Step 6: Edit is publicly visible on the creator's profile page

The scenario FAILS if any of the above is false, or if unexpected errors (500s, red error banners, blank pages) appear at any point.

---

## Known limitations / what this scenario does NOT cover

- **Account registration** (clicking "Complete your account" → setting a password → becoming a `creator` user) — that's Scenario 02.
- **Email collision path** (where the test email already belongs to a registered user) — Scenario 03.
- **Magic-link auto-login** (where clicking a token link for an already-registered creator logs them straight in) — Scenario 04.
- **Already-logged-in one-click claim** (where the user is already authenticated with a matching email) — Scenario 05.
- **Attempting to claim an already-claimed profile** — an edge case covered in a separate "failure modes" document.
- **reCAPTCHA bypass** — if reCAPTCHA is enabled on the environment, the agent may struggle to complete Step 2. reCAPTCHA is typically disabled on local (`toppingafrica.test`) and enabled on production.

---

## Notes for humans reading this file

This is a pilot scenario written for an automated browser-testing agent (Claude Dispatch). It uses natural-language step descriptions rather than CSS selectors because the agent is expected to interpret visible UI elements. If the agent struggles with step phrasing, we'll iterate on this format and rewrite all scenarios in whatever shape works best.

If the agent reports that it cannot access external domains like `maildrop.cc`, the fallback in Step 3 lets the test still run by grabbing the claim token directly from the database via tinker. This bypasses the email delivery portion of the flow but still tests the token → edit form → save → banner path.
