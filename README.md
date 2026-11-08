# XP Store - Gamification - By E.P. Studio

A gamification plugin for Moodle that transforms the learning experience by allowing students to redeem experience points (earned through `Level Up XP`) for academic accommodations, grade boosts, or exclusive content within each course.

_(**Note:** Independently developed, not affiliated with or endorsed by Level Up)._

## Key Features

* **Course-Specific Catalogs:** Each course maintains its own isolated configuration, reward catalog, and visual palette, with automatic remapping when cloning or restoring courses.
* **Store Lock & Redemption Deadlines:** Teachers can open or close all store redemptions with a single click or configure an automatic cut-off date and time after which all rewards are locked.
* **Smart Activity Rules & Mutual Exclusivity:** Built-in validation differentiates between students who missed a deadline (**Extension**) and students who submitted on time and want to improve their score (**Reattempt**), enforcing strict mutual exclusivity per activity.
* **Prerequisites & Unlocking Rules:** Require a minimum `Level Up! XP` level or completion of specific course activities before unlocking products. Choose whether locked items stay hidden or display locked with a padlock badge.
* **Non-Overridden Grade Bonuses:** Award extra points directly to activities, manual items, or the **Course Final Grade** without locking grades as overridden—teachers can regrade normally at any time while bonuses and user report feedback annotations are preserved.
* **Inventory & Purchase Limits:** Full control over maximum redemptions per student (or unlimited purchases), plus drag-and-drop catalog reordering and bulk management.
* **Modern & Responsive UI:** Clean interface with Montserrat typography, compact single-line status buttons, unified student badges, and support for FontAwesome icons or custom uploaded images.
* **Course Branding & Live Preview:** Customize primary, secondary, category, and card icon colors (with interactive gradients) or select from 18 curated presets with real-time preview.
* **Reports, Analytics & Audit Trail:** Visual engagement analytics, detailed redemption history per student with direct activity links, and full single-purchase or full-cycle rollback capabilities.
* **Embedded Widgets Ecosystem:** Ready-to-copy iframe snippets for single cards, full categories, the complete store, balance display, and history launchers.

---

### Reward Types & How Benefits Work

* ⚡ **Extra Attempt:** Works with Quizzes. Students spend XP to unlock an additional quiz attempt and a 3-day closing extension.
* ⏳ **Assignment (Extension):** Works with Assignments for students who **missed the deadline**. Grants a 3-day late submission extension. Automatically locked if the student already submitted on time or already used a Reattempt on that assignment.
* 💬 **Forum (Extension):** Works with Forums for students who **did not participate before the cut-off date**. Grants a temporary 3-day permission to post after the cut-off date (automatically revoked once the 3-day window expires). Locked if the student already posted or used a Reattempt.
* 🚀 **Reattempt:** Works with Assignments and Forums for students who **already submitted or received a grade** and want a second chance to improve their score:
  * _In Assignments:_ Automatically enables manual attempt reopening if needed and opens a **new attempt** for 3 days while preserving the original attempt and grade intact.
  * _In Forums:_ Grants a 3-day window to post additional contributions for re-evaluation.
  * _Mutual Exclusivity:_ Students who used a late **Extension** on an activity cannot redeem a **Reattempt** on that same activity, and vice versa.
* ⭐ **Bonus:** Adds extra points directly to any numeric grade item in the Moodle gradebook:
  * _Course Final Grade & Activities:_ Fully compatible with gradable activities, manual grade items, and the Course Total (Final Grade).
  * _Flexible Bonus Application Mode:_ Choose per product whether to **Apply immediately (over 0 if empty)** or **Wait for teacher grade** before adding the bonus points.
  * _Seamless Regrading:_ Bonuses synchronize dynamically without marking grades as `overridden`. Teachers can freely grade or regrade at any time; the student's bonus remains intact on top of the base grade.
  * _Transparent User Report Feedback:_ Automatically annotates the student's Gradebook User Report (e.g., `+0.5 points from XP Store`) without overwriting teacher feedback.
* 🔓 **VIP Group:** Unlocks exclusive content or access to VIP course areas. Automatically creates (or reuses) a dedicated Moodle group, restricts the target activity to that group, and enrolls the student upon purchase. Renaming a VIP product automatically migrates existing buyers to the updated group.
* 🔑 **Unlock:** Restricts access to specific course activities until purchased via XP. Choose whether the locked activity is shown with a "Restricted" label or completely hidden from the course page until unlocked. Requires the companion [XP Store Availability](https://marketplace.moodle.com/plugins/4040) plugin.

---

## Store Configuration

Ensure the `Level Up XP` block is added to your course. A direct link to the store will appear in the course navigation menu (can be toggled on/off for students).

### Configurable Options

#### 1. Reward & Product Settings
* **Level & Activity Prerequisites:** Set minimum `Level Up! XP` levels, choose locked card visibility (hidden vs. locked with padlock badge), or require another activity to be completed first.
* **Pricing & Limits:** Define XP costs and set maximum purchases per student (`0` for unlimited).
* **Target Mapping:** Link rewards to quizzes, assignments, forums, manual grade items, Course Final Grade, or VIP/unlocked activities.
* **Custom Icons & Images:** Pick built-in FontAwesome icons or upload custom image files (PNG, JPG, GIF, WEBP up to 256 KB).
* **Categories, Labels & Ordering:** Group rewards into custom category tabs, customize display labels, and reorder catalog items via drag-and-drop.

#### 2. Course-Level & Visual Settings
* **Store Open/Close & Deadline Control:** Toggle store redemptions open or closed at any time, or set an automatic expiration date/time from the top bar.
* **Color Palette & Branding:** Set custom primary, secondary, category icon, and card icon colors, or choose from 18 predefined palettes with live preview.
* **Menu Visibility:** Show or hide the store link in the course navigation menu for students.
* **Widget Generator:** Instantly copy pre-configured iframe codes for embedding into course pages, labels, or sections.
* **Purchase Management & Rollback:** Review student redemption history, undo individual redemptions (automatically refunding XP and reverting overrides, reopened attempts, groups, roles, or grade bonuses), or reset an entire user/course cycle.

---

## Widget Usage (iframes)

The configuration panel automatically generates copy-paste iframe snippets for easy embedding into any Moodle Label, Page, or Section summary (and automatically updates IDs when a course is cloned or restored):

1. **Full Store Widget:** Displays the complete catalog with category sections.
2. **Category Widget:** Displays all rewards belonging to a specific store category.
3. **Individual Card Widget:** Compact standalone card for a single product with real-time balance synchronization across widgets.
4. **Balance Widget:** Displays the student's current available XP balance.
5. **History Button Widget:** Minimalist launcher for students to check their redemption history.
