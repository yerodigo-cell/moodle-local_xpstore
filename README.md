# XP Store - Gamification - By E.P. Studio

A gamification plugin for Moodle that transforms the learning experience by allowing students to redeem experience points (earned through `Level Up XP`) for rewards, grade boosts, or exclusive content within each course.

_(**Note:** Independently developed, not affiliated with or endorsed by Level Up)._

## Key Features

* **Course-Specific Catalogs:** Each course maintains its own isolated configuration, reward catalog, and visual palette.
* **Prerequisites & Unlocking Rules:** Require a minimum `Level Up! XP` level or completion of specific course activities before unlocking products. Choose whether locked items stay hidden or display locked with a padlock badge.
* **Non-Overridden Grade Bonuses:** Award extra points directly to activities, manual items, or the **Course Final Grade** without locking grades as overridden—teachers can regrade normally while bonuses and user report feedback annotations are preserved.
* **Inventory & Purchase Limits:** Full control over maximum redemptions per student (or unlimited purchases).
* **Modern & Responsive UI:** Clean interface with Montserrat typography, unified student badges, and support for FontAwesome 6 icons or custom uploaded images.
* **Course Branding:** Customize primary and secondary colors (with interactive gradients) or select from curated palettes.
* **Reports & Audit Trail:** Detailed redemption history per student with direct activity links, avatars, and purchase rollback capabilities.
* **Embedded Widgets Ecosystem:** Ready-to-copy iframe snippets for single cards, full categories, the complete store, and floating history launchers.

---

### Reward Types & How Benefits Work

* ⚡ **Extra Attempt:** Works with Quizzes. Students spend XP to unlock an additional quiz attempt, even if the quiz is closed.
* ⏳ **Extension:** Works with Assignments. Students redeem XP for a 3-day deadline extension.
* 💬 **Forum (Legacy):** Works with Forums. Grants access to post in restricted or cut-off forums.
* ⭐ **Bonus:** Adds extra points directly to any grade item in the Moodle gradebook:
  * _Course Final Grade & Activities:_ Fully compatible with assignments, quizzes, forums, manual items, and the Course Total (Final Grade).
  * _Seamless Regrading:_ Bonuses update dynamically without marking grades as `overridden`. Teachers can freely regrade at any time; the student's bonus remains intact on top of the base grade.
  * _Transparent User Report Feedback:_ Automatically annotates the student's Gradebook User Report (e.g., `+0.5 points (XP Store)`) without overwriting teacher comments.
* 🔓 **VIP Group:** Unlocks exclusive content or access to VIP course areas. Automatically creates a dedicated Moodle group and enrolls the student upon purchase.
  * _Setup Tip:_ Do not rename the label after creation to avoid creating duplicate groups.
* 🔑 **Unlock:** Restricts access to course activities until purchased via XP. Requires the companion [XP Store Availability](https://marketplace.moodle.com/plugins/4040) plugin.

---

## Store Configuration

Ensure the `Level Up XP` block is added to your course. A direct link to the store will appear in the course navigation menu (can be toggled on/off).

### Configurable Options

#### 1. Reward & Product Settings
* **Level & Activity Prerequisites:** Set minimum Level Up! XP levels, choose locked card visibility (hidden vs. locked with padlock badge), or require another activity to be completed first.
* **Pricing & Limits:** Define XP costs and set maximum purchases per student (`0` for unlimited).
* **Target Mapping:** Link rewards to quizzes, assignments, forums, grade items, Course Final Grade, or VIP groups.
* **Custom Icons & Images:** Pick built-in FontAwesome icons or upload custom image files (PNG, JPG, WEBP up to 256 KB).
* **Categories & Labels:** Group rewards into custom category tabs with personalized titles.

#### 2. Course-Level & Visual Settings
* **Color Palette & Branding:** Set custom primary/secondary colors (gradients, badges, and icon colors) or choose from preset palettes.
* **Menu Visibility:** Show or hide the store link in the course navigation menu.
* **Widget Generator:** Instantly copy pre-configured iframe codes for embedding into course pages or labels.
* **Purchase Management:** Review student redemption history and reset or rollback transactions when needed.

---

## Widget Usage (iframes)

The configuration panel automatically generates copy-paste iframe snippets for easy embedding:

1. **Main Store Widget:** Displays the complete catalog with category tabs.
2. **Category Widget:** Displays all rewards belonging to a specific category.
3. **Individual Card Widget:** Compact card for a single product with built-in tooltips and single-line button alignment.
4. **Floating History Button:** Minimalist launcher for students to check their redemption history.
