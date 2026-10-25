# XP Store - Gamification - By E.P. Studio

A gamification plugin for Moodle that transforms the learning experience by allowing students to redeem their experience points (earned through `Level Up XP`) for rewards, grade boosts, or special content within each course.

_(**Note:** Independently developed, not affiliated with or endorsed by Level Up)._

## Key Features

* **Independent Catalogs per Course:** Each course has its own isolated configuration, product catalog, and color palette.
* **Prerequisites & Progression Requirements:** Decide if students need a particular `Level Up! XP` level or must complete another course activity before unlocking a reward. Control whether locked cards stay hidden or appear locked as motivation.
* **Purchase Limits & Inventory Control:** Total control over how many times a student can acquire a specific item or benefit (or allow unlimited purchases).
* **Modern and Responsive Design:** Clean interface with generous breathing room, native Montserrat typography, unified student badges, and support for FontAwesome 6 icons or custom uploaded images.
* **Visual Customization (UI):** Manage primary color, secondary color (interactive gradients), product icon colors, and category icon colors directly from the course interface, with 1-click predefined palettes.
* **Reports & Audit Trail:** Comprehensive dashboard for teachers featuring a detailed redemption history per student, avatars, direct links to activities or the gradebook, and purchase rollback capabilities.
* **Automatic Widget Generation:** Instant creation of Iframe codes from the configuration panel, ready to be copied and pasted without any coding.
* **Embedded Widgets Ecosystem:** Specialized views ready to be integrated into lessons or labels via Iframes (Individual cards, full Categories, and a floating History button).

---

### How Rewards Work (Benefit Types)
The store allows teachers to configure different types of benefits based on standard Moodle modules or specific gamification mechanics. When a student buys an item, the plugin processes the transaction based on its specific type:
* ⚡ **Extra Attempt:** Works with Quizzes. Students can spend XP to unlock an additional attempt for a specific quiz, even if it's already closed.
* ⏳ **Extension:** Works only with Assignments. Students can redeem their points to get extra time (3 days) to submit a task.
* 💬 **Forum (Legacy):** Works with Forums. Allows students to spend XP to unlock and interact in a specific forum even after its strict cut-off date has passed.
* ⭐ **Bonus:** Adds extra points directly to any grade item within the Moodle gradebook. It is fully compatible with assignments, quizzes, forums, manual grade items, or any other gradable element.
* 🔓 **VIP Group:** Unlocks exclusive content or access to VIP areas within the course. Upon purchase, the plugin automatically creates a dedicated Moodle group for the target activity and seamlessly assigns the student to it.
  * _Setup Tip:_ The teacher only needs to set up the reward in the store. The plugin handles the group creation and student enrollment automatically under the hood!
  * **_Important:_** Leave the label unchanged when editing this reward. Changing it will create more than one group, rendering the reward inaccessible to students.
* 🔑 **Unlock:** Works with the XP Availability plugin. Students can spend XP to unlock access to specific course modules or activities. Requires the [XP Store Availability](https://marketplace.moodle.com/plugins/4040) plugin to be installed.

---

## Store Configuration

Before using the store, ensure the `Level Up XP` block is added to your course. Once the block is active, a direct link to the store will automatically appear in the main course navigation menu (which can be toggled on or off).

### What You Can Configure

#### 1. Reward & Product Settings
* **Prerequisites & Unlocking Rules:**
  * **Level Requirements (Level Up! XP):** Decide if students need to reach a particular level (e.g., Level 5) before they can redeem a reward.
  * **Locked Card Visibility:** Choose what happens when a student has not reached the required level: either keep the card completely hidden until unlocked, or show it locked with a padlock badge to motivate progress.
  * **Activity Completion Prerequisite:** Require students to complete another course activity (assignment, quiz, SCORM, etc.) before the reward becomes purchasable.
* **Pricing & Redemption Limits:** Set flexible XP costs and specify maximum purchase limits per student (or enter `0` for unlimited redemptions).
* **Target Activity & Benefit Mapping:** Link rewards directly to quizzes, assignments, forums, gradebook items, VIP groups, or restricted modules.
* **Visual Identity & Custom Images:** Choose from a set of built-in FontAwesome icons or upload custom image assets (PNG, JPG, WEBP up to 256 KB) for each product card.
* **Categories & Custom Labels:** Organize products under custom category tabs and specify clear, engaging reward titles.

#### 2. Course-Level & Visual Settings
* **UI Color Palette & Branding:** Customize primary and secondary colors (including interactive gradients and badge styling), or pick from 6 predefined palettes.
* **Course Menu Visibility:** Toggle whether the store direct link appears in the student course navigation menu or remains hidden to direct students toward embedded widgets.
* **Category Tab Icons:** Assign distinctive FontAwesome icons to each category tab in the catalog.
* **Widget Generator:** Instantly generate ready-to-use `iframe` embed snippets for full store catalogs, single categories, individual reward cards, or the floating history launcher.
* **Purchase Management & Audit Log:** Review comprehensive student redemption history and reset or rollback transactions when necessary.

---

## Widget Usage (iframes)

💡 **Tip:** The course configuration panel includes a tool that automatically generates the exact Iframe code for each widget, ready to be copied and pasted into your lessons.

1. **Main Store Widget:** Displays all available categories and reward cards.

2. **Full Category Widget:** Displays all cards corresponding to a specific category, with a fluid and centered design.

3. **Individual Card Widget:** Displays a single product or reward available for direct purchase.

4. **Floating History Button:** A clean and minimalist shortcut for students to review their previous redemptions.
