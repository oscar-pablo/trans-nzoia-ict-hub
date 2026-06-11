# Trans-Nzoia Community ICT Hub

A website for the Trans-Nzoia Community ICT Hub — a project of **Hon. Allan Kiprotich Chesang, CAS, Senator of Trans-Nzoia County** — dedicated to empowering communities through affordable, practical digital skills training.

---

## Pages

| File | Description |
|------|-------------|
| `index.html` | Home page — photo carousel, motivational quotes wall, William Ruto quotes |
| `more.html` | More page — Hon. Chesang section, additional quotes, mission/vision, location, instructor carousel |
| `enroll.html` | Enrollment form — student registration with ID toggle, course and schedule selection |

---

## Folder Structure

```
transnzoia-ict-hub/
├── index.html
├── more.html
├── enroll.html
├── css/
│   └── style.css          # All shared styles
├── js/
│   ├── carousel.js        # Home + instructor carousel logic
│   └── enroll.js          # ID toggle + form validation
├── images/
│   ├── carousel/
│   │   ├── photo-1.jpg    # Hub interior photos for home carousel
│   │   ├── photo-2.jpg
│   │   ├── photo-3.jpg
│   │   └── photo-4.jpg
│   ├── instructors/       # Add instructor photos here
│   │   └── (add photos)
│   └── chesang.jpg        # Hon. Allan Chesang photo
└── README.md
```

---

## Adding Instructor Photos

1. Save instructor photos into `images/instructors/` named `instructor-1.jpg`, `instructor-2.jpg` etc.
2. Open `more.html` and find the `<!-- Instructor carousel -->` section.
3. Replace each `<div class="instr-placeholder-slide">` block with:

```html
<img src="images/instructors/instructor-1.jpg" alt="Instructor Name">
```

4. Update the instructor name and subject in the `<div class="instructor-info">` below it.

---

## Hosting on GitHub Pages

1. Push this folder to a GitHub repository.
2. Go to **Settings → Pages**.
3. Set source to **main branch / root**.
4. Your site will be live at `https://yourusername.github.io/transnzoia-ict-hub/`

---

## Tech Stack

- Pure HTML5, CSS3, Vanilla JavaScript — no frameworks or dependencies
- Google Fonts: Poppins + Inter
- No build tools required — open any `.html` file directly in a browser

---

&copy; 2025 Trans-Nzoia Community ICT Hub. All rights reserved.
