# Changelog

All notable changes to this extension are documented here. The format
is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [1.0.18] - 2026-10-05

### Fixed
- Slider and banner edit forms offer "All Store Views" in Store View and show the saved value, so saving a record without changes no longer moves it to Default Store View. The Manage Banners grid can show and filter "All Store Views".
- Keyword search in the Manage Sliders grid (title, identifier) and Manage Banners grid (title, alt text, link URL) filters the rows.
- The Manage Sliders grid has a mass "Delete" action and a "Store View" column; the Manage Banners grid shows "Active From" and "Active To".
- The slider identifier must start with a letter and use letters, digits, "_", "-" or "/"; a duplicate identifier for the same store view shows a clear message.
- A banner whose "Active To" date is before "Active From" is not saved and a message explains why.
- When a slider or banner cannot be saved, the form opens again with the entered values and the uploaded image; a new record returns to the New form.
- Saving sliders and banners no longer writes debug lines to the system log.
- Autoplaying sliders show a 44 px "Pause slideshow" / "Play slideshow" button (Hyva and Luma). With reduced motion the slideshow starts paused and slides change without animation.
- Arrows are 44 by 44 px at every width and become visible on keyboard focus, not only on hover; pager dots have 44 px targets; all controls show a focus ring.
- The Hyva phone control bar respects "Show Navigation Arrows" and "Show Pagination Dots".
- Hyva applies the slider "Transition Speed" setting.
- Overlay text keeps at least 4.5:1 contrast on light images, and Luma overlay headings and text use the same type scale as Hyva.
- Sliders are announced as carousels with "1 of 3" slide groups, the current dot has aria-current, hidden Luma slides cannot be reached with Tab, and Hyva no longer shows later slides over the first one before scripts load.
- Later slide images are fetched after the page loads, so the next slide is ready when it appears.
- On Hyva the slide "Link URL" also works when the slide has overlay content.
- A banner with only a tablet or mobile image shows that image at every width.
- The widget "Slider Identifier" help text points to Manage Sliders.
