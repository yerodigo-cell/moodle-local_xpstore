# XP Store for Moodle

A gamification plugin for Moodle that transforms the learning experience by allowing students to redeem their experience points (earned through `Level Up XP`) for rewards, grade boosts, or special content within each course.

## ✨ Key Features

* **Independent Catalogs per Course:** Each course has its own isolated configuration, product catalog, and color palette.
* **Purchase Limits:** Total control over how many times a student can acquire a specific item or benefit.
* **Modern and Responsive Design:** Clean interface with generous breathing room, native Montserrat typography, and support for FontAwesome 6 icons (*Solid* style).
* **Visual Customization (UI):** Manage primary color, secondary color (interactive gradients), product icon colors, and category icon colors directly from the course interface.
* **Reports:** Comprehensive dashboard for teachers featuring a detailed redemption history per student, avatars, and direct links to activities or the gradebook.
* **Automatic Widget Generation:** Instant creation of Iframe codes from the configuration panel, ready to be copied and pasted without any coding.
* **Embedded Widgets Ecosystem:** Specialized views ready to be integrated into lessons or labels via Iframes (Individual cards, full Categories, and a floating History button).

---

## 🎁 How Rewards Work (Benefit Types)

The store allows teachers to configure different types of benefits based on standard Moodle modules or specific gamification mechanics. When a student buys an item, the plugin processes the transaction based on its specific type:

* **⚡ Extra Attempt:** Works with Quizzes. Students can spend XP to unlock an additional attempt for a specific quiz even if it's already closed.
* **⏳ 24h Extension:** Works only with Assignments. Students can redeem their points to get extra time to submit a task.
* **⭐ Bonus:** Adds extra points directly to **any grade item** within the Moodle gradebook. It is fully compatible with assignments, quizzes, forums, or any other gradable element. Perfect for "Extra Credit" or "Second Chance" mechanics.
* **🔓 Reward:** Unlocks hidden content or VIP groups. Upon purchase, the student is **automatically added** to a predefined Moodle group. **Note:** The teacher must create the group in the course beforehand and configure the content's "Restrict access" settings to point to that specific group.

---

## ⚙️ Store Configuration

Once installed, the `Level Up XP` block must be active. Administrators or editing teachers can configure the store by accessing the course's exclusive administration panel:

**Path:** `yoursite.com/local/xpstore/config.php?id=COURSE_ID`

From this panel you can:

1. Define the UI color palette.
2. Visually assign FontAwesome icons to created categories.
3. Build the product catalog configuration string (defining costs, limits, and categories).
4. **Automatically generate Iframe codes** for widgets to instantly integrate them into the course.

---

## 🧩 Widget Usage (Iframes)

💡 **Tip:** The course configuration panel includes a tool that automatically generates the exact Iframe code for each widget, ready to be copied and pasted into your lessons.

### 1. Main Store Widget

Displays all available categories and reward cards.

### 2. Full Category Widget

Displays all cards corresponding to a specific category, with a fluid and centered design.

### 3. Individual Card Widget

Displays a single product or reward available for direct purchase.

### 4. Floating History Button

A clean and minimalist shortcut for students to review their previous redemptions.

---

## 📝 Release Notes

- **v1.0.0:** Initial public release.
