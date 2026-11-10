# Changelog

All notable changes to this project are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.12.2] - 2026-11-10

### Fixed

- The theme's design system page shows with its styles again. The clean-up that keeps other stylesheets off Brodfest's pages was taking the theme's page along with them.

## [0.12.1] - 2026-11-10

### Changed

- The transport heading on the accommodation page now reads "TRANSPORT".

## [0.12.0] - 2026-11-09

### Changed

- RSVP replies are now people in the WordPress Users list, not a list of
  their own. Each is a "Brodfest guest", and a plus-one is their own person,
  linked to whoever brought them. The list shows type, attending, who they
  are linked to and one Email column; every answer from the form is on the
  person's profile, under RSVP. Guests cannot sign in.
- The Users list no longer has a Posts column.
- A second reply from the same email changes that person rather than adding
  another, and a plus-one no longer brought is removed.
- Replies sent before this update are carried over into Users.
- Guests have no public author page, are never signed in, and are left out
  of the theme's Klaviyo list.

### Removed

- The RSVPs list in the admin.

## [0.11.1] - 2026-11-09

### Fixed

- On phones and tablets a tapped button or link goes back to its own colour,
  where before it kept its rollover colour until something else was tapped.

## [0.11.0] - 2026-11-09

### Added

- The Brodfest gold mark as the icon in the browser tab, until a Site Icon is
  chosen under Settings › General.

### Changed

- The home page's Add to Calendar button is hidden for now, and the line
  under it sits in its place.
- The addresses on Enquiries are info@brodfest.net and concierge@brodfest.net.
- On desktop a hotel card's description and discount line are 13px (Mark,
  9 Nov).
- The calendar event now runs 7pm to 4am Ibiza time (6pm to 3am in the UK), and its note ends
  "(7pm to 4am CEST)".
- The home page's Save the Date block is the desktop one from tablets up, so
  it only changes once, where phones take over.

### Fixed

- Apple's Add to Calendar choice adds the one event, where before Calendar
  asked to subscribe to a whole calendar.
- On Safari the menu's first link no longer shows white after a tap, as if
  it were the current page.

## [0.10.0] - 2026-11-08

### Removed

- All the animation from 0.9.0, including the light across buttons on hover,
  the opening's shine and curtain, and the GSAP dependency. The site is still
  again and the opening fades to the page as it did before (client, 8 Nov).

### Changed

- Add to Calendar's three choices now reach their calendars. Apple opens
  Calendar directly on iPhone, iPad and Mac, with nothing downloaded. Google
  and Outlook go through their sign-in with the event kept, where before a
  signed-out visitor landed on a marketing page.
- The home video only ever starts with sound. Where the browser will not allow
  that, it waits for the play button instead of playing muted.
- "Torre del Mar" and "AMA Ibiza", so each hotel name sits on one line.

## [0.9.1] - 2026-11-06

### Changed

- The home page's Save the Date block is 20% smaller on desktop.
- Add to Calendar's Apple, Google and Outlook choices sit in their own row,
  moving the line below down instead of covering it.

## [0.9.0] - 2026-11-06

### Added

- Animation across the site, slow and soft, playing once as each section
  scrolls into view: titles' letters close up into place, copy rises in line
  by line, area photos open like a curtain and drift gently on desktop, hotel
  cards rise in one after another, the map's island settles, the menu's links
  and the RSVP fields come in in turn, and the footer mountain builds up.
- All of it tuned to stay smooth while scrolling, phones included.
- The opening's logo catches a passing light once drawn, and on Enter the
  black lifts away like a curtain. The home video settles from a slight zoom.
- Gold light across the pills, BOOK filling with gold, and a slow photo zoom
  on hover; a soft pulse on a chosen RSVP answer.
- Visitors who ask for less motion get the page still, as before.

## [0.8.1] - 2026-11-06

### Added

- The AMA Ibiza Suites photo, so every hotel now has its picture.

## [0.8.0] - 2026-11-06

### Changed

- RSVP lowers over the page you are on and goes back up when you close it or
  have replied, rather than opening a page of its own and closing to the home
  page. The RSVP button closes it too.
- Add to Calendar offers Apple, Google and Outlook, so it opens a calendar on
  desktop rather than only downloading a file. The entry is "Brodfest Ibiza",
  with the line about Can Sakhara.
- The home video starts with sound where the browser allows it; where it
  does not, the sound comes on at the first tap or click.
- Enquiries: one ENQUIRIES title, Private Concierge as a subtitle, and the
  emails in the copy as bold links in place of the separate email lines.
- "Nobu Hotel" and "Mondrian", without "Ibiza Bay" and "Ibiza".
- The home note breaks after "Ibiza", and on Celebration the "A" moves down
  to the next line.
- Body copy is 30% bigger on phones and tablets.

## [0.7.3] - 2026-11-06

### Added

- The client's photos: each area's banner (a wide cut on desktop, its own on
  phones) and seven of the eight hotels. AMA Ibiza Suites' is still to come.

### Fixed

- The home video no longer changes size at 1024: tablets show the desktop
  video at the desktop size, so it only changes where phones take over.

## [0.7.2] - 2026-11-06

### Fixed

- The header no longer changes size at 1024: tablets and desktop share one
  header, so it only changes where the phone layout takes over.

## [0.7.1] - 2026-11-06

### Changed

- On phones the map fills the width of the screen, rather than sitting small
  in the middle.

## [0.7.0] - 2026-11-06

### Changed

- Everything on desktop is a quarter smaller. Phones and tablets are unchanged.
- After Enter, the home video plays in its place on the page, with the header
  as normal, rather than filling the window. It still plays by itself on later
  visits.
- Information opens with the transport details (moved from Celebration), then
  Where to Stay as a subtitle, then the map.
- Each area shows how far away it is under its name, and Bless Ibiza has lost
  "The Site".
- The site and the browser around it are #0b0603.
- Hotel and recommendation rows swipe, with dots, as soon as the window is too
  narrow to show them all.
- On RSVP, "Other" is the last dietary choice.

### Fixed

- No pink flash while the home video loads.
- Desktop shows the client's wide map, and mobile their squarer one.
- Links no longer show a box after a tap or click; keyboard users still see
  where they are.

## [0.6.1] - 2026-11-06

### Changed

- The Enter screen is back: the logo draws itself on black, then Enter appears
  under it. Pressing it plays the home video with sound, filling the window.
  On later visits the video still plays by itself, muted.

## [0.6.0] - 2026-11-05

### Added

- The home page opens on black as the Brodfest logo draws itself, then fades
  to the page, on a visitor's first arrival. It replaces the Enter screen.
- The home video plays by itself, muted, filling the window over the menu, with
  a sound button. Scrolling, tapping or pressing Esc brings the page back, and
  the video plays on in its place. Anyone who asks for less motion gets the
  page and a play button instead.

### Fixed

- The menu button and RSVP sit level with the logo, centred in the header.
- RSVP: the country code, the dietary choice and the phone placeholder sit in
  the middle of their fields.
- Each row of hotels has its BOOK buttons in line, and the AMA Ibiza link
  works.

### Changed

- The home page, and the calendar entry's note, say "Your invitation and the
  full Brodfest Ibiza website will land before Christmas.", as in the copy
  document.

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
