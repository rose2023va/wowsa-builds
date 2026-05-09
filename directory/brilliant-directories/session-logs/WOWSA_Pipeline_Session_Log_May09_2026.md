# WOWSA Global Directory
## Pipeline Session Log - May 9, 2026

---

## What We Did

1. Reviewed the existing `DirectoryPipeline_May06.js` script in Google Apps Script to understand the current state of the enrichment pipeline before making changes.

2. Confirmed the pipeline architecture: Script 1 `runClassification()` reads from "Scraped Data" view, Script 2 `runEnrichment()` reads from "Pipeline - Ready for Enrichment" view, Script 6 `runContactResearch()` reads from "Ready for Social Research" view. Scripts 3, 4 (Directorist push and sync) and Script 5 (Gmail outreach) had not yet been commented out.

3. Decided to add NeverBounce email validation to the pipeline. Context: a previous mass send produced approximately 80% bounce and undeliverable rates due to unvalidated AI-sourced email addresses. NeverBounce account confirmed active with 1,000 credits on pay-as-you-go plan.

4. Scoped the NeverBounce integration: three total email search attempts per record, each attempt using a progressively broader Claude web search strategy. NeverBounce result codes `invalid`, `disposable`, `unknown`, and `accept_all` all treated as failures requiring retry. Only `valid` passes through. `unknown` and `accept_all` explained: unknown means the mail server did not respond; accept_all means the domain accepts all email regardless of whether the mailbox exists - both unconfirmed for this audience of small event organisers with custom domains.

5. Created NeverBounce API key in NeverBounce dashboard under Settings > API Keys. Key format is `private_xxxx`.

6. Added `NEVERBOUNCE_API_KEY: 'private_xxxx'` to the `CONFIG` object in the Apps Script config file. Initial attempt failed with `SyntaxError: Unexpected identifier 'NEVERBOUNCE_API_KEY' line 18` - resolved by adding missing comma after `DIRECTORY_ID: 89` on the preceding line. Confirmed the last CONFIG entry does not need a trailing comma.

7. Added `validateEmailNeverBounce()` helper function to the script. Calls the NeverBounce v4 single verify endpoint via GET request with key and email as query parameters. Returns one of: `valid`, `invalid`, `disposable`, `unknown`, `accept_all`, or `error`. API error returns `error` and does not pass the email through.

8. Added `searchEmailRetry()` function with two search strategies. Attempt 2 uses a broad web search for the event name combined with terms like "contact", "email", "register", "info" across all sources including Facebook, Instagram, and event listing sites. Attempt 3 searches for the race director or organiser name tied to the event, tries direct email patterns on the event domain, and checks LinkedIn.

9. Integrated the NeverBounce retry block into `runEnrichment()`. Original attempt count: up to 3 total (attempt 1 from `enrichWithClaude()`, attempts 2 and 3 from `searchEmailRetry()`). All failures set Claude Status to Needs Review and Claude Notes to "No valid email found after 3 attempts. Manual review required."

10. Commented out Scripts 3 and 4 (Directorist push and sync) in a `/* */` block comment at lines 827-1138. Functions wrapped: `pushToDirectorist()`, `findExistingListing()`, `createWordPressPost()`, `buildMeta()`, `writeMeta()`, `getListingUrl()`, `uploadImageToWordPress()`, `syncPublishedListings()`. Reason: migrating from Directorist to Brilliant Directories; manual Airtable-to-BD import used in interim.

11. Commented out Script 5 (Gmail outreach) in a `/* */` block comment at lines 1148-1339. Functions wrapped: `getClaimUrl()`, `getFirstName()`, `isGenericEmail()`, `getSalutation()`, `buildDay1Body()`, `buildDay3Body()`, `buildDay7Body()`, `buildDay14Body()`, `runOutreach()`, `fetchOutreachRecords()`, `sendEmail()`, `createGmailDraft()`. Reason: outreach moving to Brevo; manual contact import used in interim.

12. Generated a Word document `WOWSA_Outreach_Email_Sequence.docx` extracting all four outreach emails from the commented-out script for use when rebuilding the sequence in Brevo. Document includes subject lines, triggers, full body copy, salutation rules, deduplication logic, claim URL fallback, and sender details.

13. Identified a problem with the original NeverBounce integration in `runEnrichment()`: the function is heavy (15 second sleep per record, multiple API calls for description, photo, and email search) making it unsuitable for bulk re-processing of existing data. Existing records either had unvalidated emails or empty email fields and had never been through NeverBounce.

14. Redesigned the email validation flow into three separate functions to allow existing data to be processed without re-running the full enrichment. Agreed flow:

    Classified - runEnrichment - Email Validation or Retry Email Search - runEmailValidation or runEmailSearch - Ready or Needs Review.

    `runEnrichment()` routes to Email Validation if email found, Retry Email Search if no email found. `runEmailValidation()` validates existing emails via NeverBounce. `runEmailSearch()` searches for missing emails with up to 3 attempts tracked by counter.

15. Simplified `runEnrichment()` by removing the entire NeverBounce retry block. New behavior: enriches description and photo only, writes email if Claude finds one, sets Claude Status to Email Validation if email present or Retry Email Search if no email found. No NeverBounce calls in this function.

16. Added new `runEmailValidation()` function reading from "Email Validation" Airtable view. Validates existing Contact Email via NeverBounce. Valid result sets Claude Status to Ready and writes "Email validated by NeverBounce" to Claude Notes. Invalid/unknown/accept_all/disposable clears Contact Email and Contact Full Name fields and sets Claude Status to Retry Email Search. NeverBounce API error sets Claude Status to Needs Review without clearing the email.

17. Added new `runEmailSearch()` function reading from "Retry Email Search" Airtable view. Runs one search attempt per execution using `searchEmailRetry()` - strategy 2 (broad web search) on attempts 1 and 2, strategy 3 (organiser name and domain patterns) on attempt 3. Validates found email via NeverBounce. Valid sets Claude Status to Ready and writes email and name to Airtable. Failed validation increments Email Search Attempts counter and stays in Retry Email Search for next run. Third failed attempt sets Claude Status to Needs Review. NeverBounce API error increments counter and stays in retry loop.

18. Added Email Search Attempts as a number field to the Airtable Main table in the Directory Pipeline base (appgCfXVeizy2WJrp).

19. Created two new Claude Status values in Airtable: Email Validation and Retry Email Search.

20. Created "Email Validation" view in Airtable Main table with filters: Claude Status is Email Validation AND Contact Email is not empty AND Filter Flag is Open Water.

21. Created "Retry Email Search" view in Airtable Main table with filters: Claude Status is Retry Email Search AND Filter Flag is Open Water AND condition group (Email Search Attempts < 3 OR Email Search Attempts is empty). The condition group uses Airtable's OR logic within a group to handle both new records with no counter and records mid-loop.

22. Updated `updateEnrichment()` statusMap to include Email Validation and Retry Email Search as recognised values.

23. Updated "Ready for Social Research" view filter by removing the Claude Status = Ready condition. New filters: Contact Email is empty AND Social Research Status is empty AND Filter Flag is Open Water. Reason: records failing email validation would be set to Retry Email Search or Needs Review, not Ready, so the old filter would have excluded them from social research permanently.

24. Bulk updated existing Airtable records: records with existing Contact Email set to Claude Status = Email Validation; records with empty Contact Email set to Claude Status = Retry Email Search.

25. Tested `runEmailValidation()` manually in Apps Script. Execution log confirmed: 2 records processed, both emails returned valid by NeverBounce, both set to Ready. 17 seconds total. 0 errors.

26. Tested `runEmailSearch()` manually in Apps Script. Execution log confirmed: 2 records processed. ATL - Cultus Lake found email `cultuslake@acrossthelakeswim.com`, NeverBounce returned valid, set to Ready. 99th Annual Goguac Lake Swim found email `scheduling@hamptonaquatics.com`, NeverBounce returned unknown, Email Search Attempts incremented to 1, stayed in Retry Email Search for next run. Verified in Airtable: Claude Status = Retry Email Search, Email Search Attempts = 1, Claude Notes = "Email found but failed NeverBounce (unknown) on attempt 1. Will retry."

27. Set up Apps Script time-based triggers: `runClassification()` every 15 minutes, `runEnrichment()` every 1 hour, `runEmailValidation()` every 5 minutes, `runEmailSearch()` every 10 minutes, `runContactResearch()` every 1 hour.

---

## Errors Encountered

**SyntaxError on NEVERBOUNCE_API_KEY**
- Triggered by: adding `NEVERBOUNCE_API_KEY: 'private_xxxx'` to the CONFIG object in Apps Script
- Error: `SyntaxError: Unexpected identifier 'NEVERBOUNCE_API_KEY' line 18 file: Code.gs`
- What was tried: checked that the key value had correct opening and closing quotes and comma
- What worked: identified that `DIRECTORY_ID: 89` on line 17 was missing a trailing comma; adding the comma resolved the error immediately

---

## Workarounds Tested

**Resetting Claude Status to Classified to re-process existing data**
Considered resetting all existing records to Classified so they would flow back through `runEnrichment()` for email re-processing. Rejected because `runEnrichment()` rewrites the description and photo on every run, which would overwrite existing good descriptions and consume unnecessary Claude API credits and time. The separate `runEmailValidation()` and `runEmailSearch()` functions were built specifically to avoid this.

**Single combined email validation function**
Considered building one function that both validated existing emails and searched for missing ones. Rejected because the two tasks have different triggers, different speeds, and different counter logic. Keeping them separate allows independent trigger scheduling and avoids one slow task blocking the other.

**Treating accept_all as valid**
Considered passing accept_all emails through as valid since the WOWSA audience uses many small custom domains that commonly return accept_all. Rejected because accept_all gives no confirmation the mailbox exists and the original bounce problem was caused by passing through unconfirmed emails. All unconfirmed results trigger a retry search instead.

---

## Final Decision

Implemented a three-stage email pipeline: `runEmailValidation()` for existing emails, `runEmailSearch()` for missing emails with a 3-attempt counter, and simplified `runEnrichment()` that routes to the appropriate stage rather than handling validation itself. Scripts 3, 4, and 5 (Directorist push, sync, and Gmail outreach) are commented out in block comments and retained for reference. All pipeline data accumulates in Airtable for manual import to Brilliant Directories. Outreach sequence documented in `WOWSA_Outreach_Email_Sequence.docx` for Brevo rebuild.
