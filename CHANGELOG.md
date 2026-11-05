# Changelog

All notable changes to this project are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.5.0] - 2026-11-05

### Added

- The Save the Date site has the client's copy: Celebration (with Dress Code
  and Transport), Where to Stay with its three areas and eight hotels, and
  Enquiries with the brodfestibiza.net addresses. Only the Cala Llonga hotels
  show a discount, with the 25% offer.
- The home page plays the Save the Date video: the wide cut on desktop, the
  square one on phones.
- RSVP: a sent reply shows "Thank you for your RSVP" and a Close button. The
  dietary list has Dairy Free.

### Changed

- The header is 170px tall on tablets and desktop (phones keep 207px), and
  never sits nearer the edge than 48px.
- The menu is renamed Celebration and Information on the Save the Date. When
  open it blurs the page behind, has no mountain, and its items turn white on
  rollover instead of bold.
- The opening screen has no line under the logo and no ring around Enter, which
  sits a little lower and smaller.
- Add to Calendar books 7pm Ibiza time instead of all day.
- The RSVP country code is a list to choose from, still showing +44 first. The
  dietary list opens dark with gold lettering.

## [0.4.1] - 2026-11-05

### Changed

- Laptops (1024–1599px wide) now keep every height and text size from Figma,
  at the client's request. Instead of the whole design shrinking, only widths
  narrow: content stays within 1564px with an 18px margin, the map and
  banners crop at the sides, and text can wrap onto an extra line. Hotel
  cards keep their size and swipe, with dots under them.

## [0.4.0] - 2026-11-04

### Changed

- Every page now shows Figma's exact sizes: a 50px box is 50px. This holds on
  screens 1600px and wider (the design stays centred) and in the mobile design.
- Laptops narrower than 1600px show the desktop design shrunk evenly, like the
  Figma file zoomed to fit, since the card rows don't fit at full size.
- Phones show the mobile design zoomed to fit the screen.

## [0.3.0] - 2026-11-04

### Changed

- Colours and fonts now come from BlueWorx › Design in the Enhance theme, so
  they can be changed there. A Brodfest preset sets them to the design in one
  click. **After updating, apply the Brodfest preset on BlueWorx › Design, or
  the site shows the theme's default colours.**
- The logo, icons and lettering follow those colours too.
- Every page now matches the Figma design at desktop and mobile size: spacing,
  line heights, the RSVP form's radios, arrows and accessibility line, and the
  placeholder copy.

### Fixed

- The open menu has the page colour behind it, instead of taking on the colour
  of the video underneath.

## [0.2.0] - 2026-11-03

### Added

- The whole Save the Date site from the Figma design, on desktop and mobile:
  Home, Celebrate, Accommodation, Enquiries and RSVP.
- The Invite site: its own Home and Celebrate, plus Can Sakhara Story and Ibiza
  Recommendations. Switch between the two under Brodfest in the admin; editors
  can preview the Invite first.
- A first-visit intro that ends on Enter, so the home video can play once with
  sound. After that a replay button shows instead.
- The menu slides in over a blurred page, and the header hides as you scroll
  down and comes back as you scroll up.
- Add to Calendar downloads the date for any calendar app.
- RSVP replies are saved under RSVPs in the admin and emailed to the site.
- Hotels and recommendations swipe on mobile.
- Brand fonts from the client's Adobe Fonts kit.

### Changed

- Transport is gone from both menus; its copy will sit on Accommodation.
- Type sizes and spacing rounded to whole numbers, as the design comments asked.
- Text on mobile has double the side margins; images keep the narrow ones.

## [0.1.0] - 2026-11-03

### Added

- The Brodfest website as a WordPress plugin, ready for the design to be built
  in: activating it creates the Home page and makes it the front page, with a
  placeholder header and footer.
- Its pages are marked as Brodfest pages in the Pages list on the BlueWorx
  Enhance theme, so they can't be deleted by accident.
- Updates install automatically from GitHub Releases.
