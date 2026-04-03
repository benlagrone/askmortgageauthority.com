# Intake And Prequalification Plan

This document defines how Ask Mortgage Authority should add intake and
prequalification into the existing site without turning WordPress into a full
loan application system.

## Goal

Add a structured, high-intent intake flow that:

- helps visitors self-identify the right next step
- captures enough information to route and prioritize leads
- feeds clean structured data into EspoCRM
- keeps the public site at a soft-prequalification level
- avoids collecting sensitive application data in WordPress

## Current Site Context

The site already has the core pieces of a funnel:

- a primary CTA to `get-pre-qualified` appears in page automation config
- calculator pages already push users toward prequalification
- the theme includes a chat iframe surface
- a mu-plugin already syncs form submissions into EspoCRM

Relevant files:

- `automation/config/pages.json`
- `automation/config/calculators.json`
- `automation/config/menus.json`
- `custom/wp-content/mu-plugins/ama-crm-form-sync.php`
- `custom/wp-content/themes/lecrown/chat.js`

The gap is not lead capture in general. The gap is structured intake data.
Right now, the CRM sync is oriented around name, email, phone, and notes. That
is enough for a contact form, but not enough for mortgage intake, routing, or
prioritization.

## Product Direction

The intake flow should be staged.

### Stage 1: Quick Intake

Ask only the questions that help identify scenario fit and urgency:

- `loan purpose`
  - purchase
  - refinance
  - cash-out refinance
- `property use`
  - primary residence
  - second home
  - investment property
- `property state`
- `purchase price range` or `estimated home value`
- `down payment range` or `equity range`
- `credit score band`
- `household income band`
- `employment type`
- `timeline`
  - now
  - 30-90 days
  - 3-6 months
  - 6+ months
- `first-time buyer`
- `veteran / active-duty eligibility`

This stage should feel lightweight. It is a fit-check, not an application.

### Stage 2: Contact And Consent

Ask for:

- first name
- last name
- email
- phone
- preferred contact method
- preferred contact time
- consent to be contacted

After submit, show a soft result:

- likely loan path
- estimated next step
- expectation for follow-up timing

Examples:

- "This looks like an FHA or conventional review."
- "This looks like a refinance consultation."
- "A mortgage advisor will review your scenario and confirm options."

### Stage 3: Full Application Or Secure Document Collection

Do not collect these items inside WordPress:

- Social Security number
- date of birth
- driver's license
- bank statements
- tax returns
- pay stubs
- full asset account numbers

If the lead is qualified enough to proceed, hand off into the lender's secure
portal, LOS, or encrypted document collection workflow.

## Recommended UX

The site should offer three entry points into the same intake model.

### 1. Main Prequalification Page

`/get-pre-qualified/` becomes the canonical intake page.

This page should contain:

- short promise
- "no hard credit pull" or similar approved language only if accurate
- short list of what the visitor will need
- 2-step form
- reassurance about timing and privacy

### 2. Embedded CTA From Program Pages

Loan program pages should keep their current CTA placement, but the CTA should
carry context into intake:

- FHA page -> prefill `loan_program_interest=fha`
- VA page -> prefill `loan_program_interest=va`
- conventional page -> prefill `loan_program_interest=conventional`
- refinance page -> prefill `loan_program_interest=refinance`

This preserves the current automated page structure while increasing form
relevance.

### 3. Calculator-To-Intake Handoff

Calculator hub pages should offer a "use this scenario in prequalification"
handoff with hidden fields such as:

- calculator name
- estimated payment scenario
- source page

Even if the calculator tool cannot pass raw values yet, the site should still
capture:

- `lead_source_detail=calculator`
- `calculator_key`
- `calculator_page`

### 4. Chat-Assisted Entry

The existing chat iframe can become a softer intake surface.

Use the same question model in chat:

- identify purpose
- identify timeline
- identify price/equity band
- identify contact details

Chat should not become a separate intake system with different fields and
different routing rules. It should either:

- push answers into the same CRM schema, or
- hand off into the same prequalification form with fields prefilled

## Recommended Data Model

These fields should be captured explicitly instead of buried in `description`.

### Core Contact Fields

- `firstName`
- `lastName`
- `emailAddress`
- `phoneNumber`

### Intake Fields

- `loanPurpose`
- `propertyUse`
- `propertyState`
- `loanProgramInterest`
- `priceRange`
- `downPaymentRange`
- `equityRange`
- `creditBand`
- `incomeBand`
- `employmentType`
- `timeline`
- `firstTimeBuyer`
- `vaEligible`
- `preferredContactMethod`
- `preferredContactTime`
- `consentToContact`

### Attribution Fields

- `source`
- `sourcePage`
- `calculatorKey`
- `chatOrPage`
- `utmSource`
- `utmMedium`
- `utmCampaign`

### Routing Fields

- `leadIntent`
- `leadPriority`
- `productType`
- `businessUnit`

## CRM Behavior

EspoCRM should not just receive a flat lead. It should receive a lead that can
be routed immediately.

Examples:

- purchase + primary residence + near-term timeline -> high-priority purchase lead
- refinance + high equity band -> refinance queue
- VA eligible -> VA specialist queue
- low-information chat lead -> nurture / callback queue

This can start simple. Even a few deterministic routing rules will outperform a
single notes field.

## How This Fits The Current Codebase

### Existing Form Sync

`custom/wp-content/mu-plugins/ama-crm-form-sync.php` already:

- listens to WPForms and Forminator submissions
- normalizes common contact fields
- builds an EspoCRM lead payload
- submits the lead over the EspoCRM API

The next step is to expand normalization and payload mapping so the plugin
recognizes mortgage-specific fields and sends them as first-class CRM
properties.

### Existing Automated Pages

`automation/config/pages.json` and `automation/config/calculators.json` already
centralize CTA copy and URLs. That means intake can be added into the current
site without rebuilding the entire publishing workflow.

Recommended changes:

- keep `/get-pre-qualified/` as the canonical CTA target
- add contextual query parameters or hidden-field presets from each page type
- optionally add a new automation-managed intake block later

### Existing Chat Surface

`custom/wp-content/themes/lecrown/chat.js` injects a dedicated chat iframe. That
creates an immediate path for guided intake without redesigning the whole theme.

The constraint is consistency: chat and page form should share one intake model,
one CRM payload shape, and one routing logic.

## How To Add It With Least Risk

The lowest-risk rollout is a split between WordPress admin changes and repo
changes.

### WordPress Admin Changes

These do not require theme or automation rewrites:

- convert the existing prequalification form into a multi-step Forminator form
- add the intake questions as explicit fields
- add hidden fields for attribution and context
- update the `/get-pre-qualified/` page copy and success state

This is the fastest way to improve conversion and lead quality.

### Repo Changes

These should be made in code so the intake model is stable and repeatable:

- extend `ama-crm-form-sync.php` to map mortgage intake fields into EspoCRM
- update automation-managed CTA URLs to include context
- add a small helper for query-string-to-hidden-field prefills if Forminator
  alone does not cover the need
- standardize the chat handoff contract

This keeps WordPress editing simple while protecting the data contract in code.

## Proposed URL And Hidden Field Contract

Every intake entry point should be able to pass a small, standard set of
context fields to `/get-pre-qualified/`.

Suggested query params:

- `source_page`
- `lead_source_detail`
- `loan_program_interest`
- `calculator_key`
- `chat_or_page`
- `utm_source`
- `utm_medium`
- `utm_campaign`

Example links:

- `/get-pre-qualified/?source_page=fha-loans&loan_program_interest=fha&chat_or_page=page`
- `/get-pre-qualified/?source_page=mortgage-tools&lead_source_detail=calculator&calculator_key=payment_amortization&chat_or_page=page`
- `/get-pre-qualified/?source_page=chat&chat_or_page=chat`

These values should land in hidden form fields and then pass through to the CRM
payload.

## Recommended Technical Shape

### 1. Keep One Canonical Form

Do not create separate forms for FHA, VA, conventional, calculators, and chat
unless there is a clear conversion reason. Use one canonical intake schema with
source context.

Why:

- simpler CRM mapping
- fewer forms to maintain
- consistent reporting
- easier future handoff into an LOS

### 2. Expand The CRM Sync Instead Of Replacing It

The existing mu-plugin is the correct integration point.

Recommended plugin changes:

- add alias matching for mortgage labels such as `loan purpose`, `property use`,
  `credit score`, `timeline`, and `down payment`
- preserve a normalized array of all recognized intake fields
- map recognized fields into EspoCRM custom properties
- append any leftover unmapped fields into `description`
- log unknown labels when debug logging is enabled

This keeps the current integration model and avoids introducing another webhook
or public endpoint.

### 3. Use Chat As A Handoff First

For v1, the chat experience should not submit directly into EspoCRM unless its
backend is already controlled and trustworthy.

Preferred v1 behavior:

- collect basic scenario details in chat
- hand off to `/get-pre-qualified/` with query params
- prefill what can be prefilled
- let the canonical form create the actual CRM lead

That avoids two competing intake systems.

### 4. Keep Automation Changes Small

The automation layer already controls the main money-page CTAs. Use that first.

Initial updates should be limited to:

- FHA CTA URL carries FHA context
- VA CTA URL carries VA context
- conventional CTA URL carries conventional context
- refinance CTA URL carries refinance context
- calculator hub CTA URL carries calculator context

That is enough to improve attribution without expanding the rendering system.

## Implementation Plan

### Phase 1: Structured Prequal Form

Create or revise the Forminator form on `/get-pre-qualified/` so it captures the
recommended Stage 1 and Stage 2 fields.

Deliverables:

- one canonical prequalification form
- hidden attribution fields
- clearer success state
- form IDs added to CRM sync allowlist if needed

### Phase 2: CRM Field Mapping

Extend the mu-plugin to map mortgage intake labels into CRM fields.

Deliverables:

- normalized field map for common mortgage labels
- payload support for new EspoCRM properties
- improved description block for anything not yet modeled explicitly
- logging for unmapped but high-value fields

### Phase 3: Contextual Entry Points

Use the existing automated pages and calculators to drive better-qualified form
starts.

Deliverables:

- CTA links with source context
- loan-page-specific presets
- calculator-to-intake attribution
- chat handoff alignment

### Phase 4: Routing And Follow-Up

Add CRM-side rules to assign and prioritize leads.

Deliverables:

- priority tiers
- queue or owner assignment rules
- fast follow-up expectations for high-intent leads

### Phase 5: Secure Handoff

Once the soft-prequal funnel performs well, add the secure application or
document upload handoff outside WordPress.

## Suggested First Version

The first version should stay intentionally small:

1. Keep one intake page.
2. Use one multi-step Forminator form.
3. Capture 8-12 qualification fields, not 30.
4. Send those fields to EspoCRM as structured data.
5. Add CTA context from loan pages and calculators.

That gets the site from generic lead capture to real intake without creating a
fragile mortgage application inside the marketing site.

## Concrete First Build

If this were implemented now, the first build should look like this:

1. In WordPress admin, revise the existing prequalification form into a
   multi-step Forminator form.
2. In code, extend `custom/wp-content/mu-plugins/ama-crm-form-sync.php` so it
   recognizes and sends the new intake fields.
3. In `automation/config/pages.json`, update each loan CTA URL to include source
   context.
4. In `automation/config/calculators.json`, update the calculator CTA URL to
   include calculator context.
5. In chat, use a link handoff to the same canonical form rather than building a
   second lead pipeline.

This path is small enough to ship quickly and strong enough to support later
routing, analytics, and secure-portal handoff.

## Open Questions

These questions matter before implementation:

- Which EspoCRM custom fields already exist, if any?
- Is "no hard credit pull" approved and accurate for every prequal path?
- Should refinance and purchase use one shared form or two variants?
- Does the chat system already have a backend that can post structured lead
  data?
- Is there an external LOS or secure portal that should receive handoffs?

## Recommended Next Build Tasks

1. Audit the existing `/get-pre-qualified/` form fields and identify its form
   ID.
2. Define the exact EspoCRM field names for the intake model.
3. Extend `ama-crm-form-sync.php` to normalize and map those fields.
4. Update CTA links in automation config to pass source context.
5. Standardize the chat handoff to the same intake schema.
