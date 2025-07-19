# Product Requirements Document (PRD)

## Project Name: Repost Intelligence Plugin for WordPress

---

## Overview
A WordPress plugin designed to help digital creators improve their chances of being reposted on X (formerly Twitter). The plugin analyzes repost patterns and uses AI to generate personalized, on-brand content for the user. It integrates with existing WooCommerce subscriptions and utilizes user meta fields for deep personalization. This plugin operates entirely within the current WordPress ecosystem.

**Target Users:**
- Digital creators using the existing WordPress membership site.
- Solopreneurs looking to grow their reach via reposts.
- WooCommerce-based subscribers.

**Problem It Solves:**
Creators often post blindly without data on what big accounts tend to repost. This plugin makes repost behaviors visible and actionable while generating AI-assisted content tailored to the user's unique context.

---

## Core Features

| Feature               | Description                                                |
|------------------------|------------------------------------------------------------|
| User Profile Context   | Uses existing user_meta fields (writing style, offer, audience, pain points). |
| Repost Scraper         | Fetches repost behaviors from specified X accounts via API. |
| Pattern Analyzer       | Identifies repost-worthy tweet structures: tone, length, format. |
| AI Content Generator   | Generates personalized content using OpenAI based on user_meta. |
| Scheduling / Posting   | (Optional) Posts or schedules drafts to X via API.         |
| User Dashboard         | WordPress Admin/front-end UI for patterns and AI outputs.   |
| WooCommerce Integration| Controls feature access through subscription tiers.        |

---

## User Experience (WordPress Flow)
1. User logs in via existing site.
2. User completes Personal Context fields.
3. Visits Repost Engine Dashboard:
   - View repost patterns for chosen big accounts.
   - Receive AI-generated drafts tailored to their profile.
4. Approves/schedules posts (optional).
5. Subscription tiers limit or expand AI usage.

---

## Technical Architecture

| Component        | Technology                                   |
|------------------|-----------------------------------------------|
| CMS              | WordPress (existing site)                      |
| User Management  | WP Users + user_meta                           |
| Payments         | WooCommerce Subscriptions                      |
| Scraping         | PHP (twitter-api-v2) + WP Cron                 |
| AI               | OpenAI API via PHP integration                 |
| Data Storage     | WP user_meta + custom reposts table            |
| UI               | WP Admin Pages / Shortcodes / REST API         |
| Scheduling       | X API integration via PHP                      |

---

## Development Roadmap
### Phase 1 (MVP)
- Admin settings: target accounts, API keys.
- User Meta integration.
- Repost scraping and storage.
- User dashboard: repost patterns.
- AI generator: basic prompt, outputs drafts.

### Phase 2
- Enhanced AI prompts (few-shot with repost examples).
- Scheduling feature.
- WooCommerce-based quotas.
- Analytics dashboard (repost likelihood, engagement trends).

### Phase 3 (Optional)
- Chrome extension scraping fallback.
- Multi-account management for agencies.
- Expansion to additional social platforms.

---

## Logical Dependency Chain
1. User Meta → Scraper → AI Generator (all outputs depend on these inputs).
2. WooCommerce subscription gates access.
3. Analytics relies on ongoing usage/posting data.

---

## Risks & Mitigations
| Risk                              | Mitigation                              |
|-----------------------------------|------------------------------------------|
| X API limitations or changes       | Chrome extension fallback.                |
| AI output inconsistency            | Better prompts via structured user_meta.   |
| WordPress performance (cron-heavy) | Limit frequency, use async HTTP requests. |

---

## Appendix
**Database Table Example: `reposts`**
- id (PK)
- source_handle
- original_tweet_id
- original_text
- timestamp

**User Meta Fields (Existing):**
- personal-context
- dream-client
- writing-style
- irresistible-offer
- dream-client-pain-points
- ikigai
- topic

---

## Positioning Clarification
This PRD is scoped for a **plugin product** offered to other WordPress creators, with WooCommerce as the core access control.

