# Changelog

All notable changes to this project are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.17.1] - 2026-11-25

### Fixed

- A page with a password, made from a Brodfest layout, now asks for the password before it shows anything.
- A page that mixes Brodfest sections with other content keeps the theme's own page, so things like shortcodes work on it.
- The RSVP form returns a guest to the page they sent it from, with the thank-you or error message, and no page with the form is cached.
- The editor loads the newest calendar block controls instead of an old saved copy.

## [0.17.0] - 2026-11-25

### Added

- Every page is now locked by BlueWorx's shared page lock. Logged in as a client, you can change words, images, links and list items, and show or hide a section, but you cannot add, move, remove or unlock one; a save through the website's API (as a connected tool makes) that tries to change the sections is refused. In the editor a client sees only the Brodfest sections, no code editor and no add-block button, and the page structure open. The held-back Invite pages are locked the same way, and the seven pages that already exist are locked on the first visit after the update.
- A "Show this section on the site" switch on every section, in the block tab of the editor. A hidden section stays on the page but visitors do not see it.
- A whole-page layout for each page type (Home, Celebrate, Can Sakhara Story, Accommodation, Ibiza Recommendations, Enquiries, RSVP), under "Brodfest pages" in the pattern list, so a new page starts from one with its words already in it. Only the live site's layouts are offered, so the Invite's words stay private until it is live. A page made from a layout looks and works like the others.

### Changed

- A page now draws only the words saved in it. Nothing is filled in by code when it loads: a page, or held-back page, that is empty shows empty.

## [0.16.0] - 2026-11-24

### Changed

- Every section of the Save the Date and of the hidden Invite is now a section block, drawn on the server: the opening screen, the home video, the date and lettering, Add to Calendar, the text sections (Celebration, dress code, transport, enquiries and the rest), the Can Sakhara Story banner and feature image, the map, each accommodation area with its hotels, each recommendations group, the RSVP form, the header and the footer. The pages look exactly as they did on a computer, a tablet and a phone; the one difference is that screen readers now hear the Information page’s areas as headings under "Where to stay", and the Invite’s Enquiries page has one visible main heading instead of a hidden one.
- Each page now holds its words as blocks, so they can be edited in WordPress. A page is given its blocks once, when the plugin updates; after that its words belong to the page and an update never overwrites them.
- The Invite’s blocks are kept in a private "Held-back pages" copy of each page (under Pages in the admin) until the live site is switched to the Invite, so nothing of the Invite can be read before then. Switching the live site in Brodfest › Site swaps each page’s blocks with its held-back copy, and switching back puts them back exactly.
- A held-back page can never be published, so the Invite cannot be read early. The website changes site only once the pages have, so a failed switch leaves it where it was. One lock covers starting blocks, switching and editor saves; a switch keeps a recovery copy of both versions, reads every write back, puts every page back if one fails (an administrator notice says which, and where the copies are), and makes a deleted held-back page again from the starting content. A browser tab opened before a switch cannot save over the new blocks, a revision made for the other site cannot be restored, and WordPress Preview shows unsaved changes to a page.
- Every image on a block has an alt text field, or a "decorative" switch. The hotel and area photos and the map now have alt text. Lists (the hotels in an area, the places in a group) have a smallest and largest size, and a list with nothing in it leaves itself out of the page.
- Each section’s styles now sit beside it in `blocks/` and use the theme’s named fonts and sizes (`--font-*`, `--text-*`), with today’s value behind each name until the theme has it. New names for the theme and Figma: `--text-13`, `--text-18`, `--text-20`, `--text-22`, `--text-23`, `--text-25`, `--text-26`, `--text-27`, `--text-30`, `--text-34`, `--text-35`, `--text-37`, `--text-48` and `--text-50`.

### Removed

- The fixed PHP page templates (`templates/pages/`, `templates/parts/`) and the copy held in code: the blocks and the pages’ own content replace them.

## [0.15.3] - 2026-11-24

### Added

- A check of the plugin against our current Foundation rules, and of the hidden Invite pages against Mark’s V5.01 design, in docs/foundation-check-2026-11-24.md. Notes only: nothing on the live site changes.

## [0.15.2] - 2026-11-14

### Changed

- Cala Llonga on the Information page now reads "(25 mins away)" instead of 20. From Mark’s Pastel comment, 13 Nov.

## [0.15.1] - 2026-11-12

### Changed

- RSVP form wording now uses lower case after the first word ("Full name", "Partner’s email address", "Dairy free" and so on), and the home page line about the invitation no longer ends in a full stop. From Mark’s Pastel comments, 12 Nov.

## [0.15.0] - 2026-11-12

### Added

- A "Download guest list" button above the Users list. The spreadsheet has exactly Mark's 19 columns in his order, one row per reply with the partner on the same row, and nothing else. Extra Guests, Hotel, Transport and Concierge are left empty for the client to fill in.

### Fixed

- RSVP replies are no longer lost. The form kept only five replies an hour from one internet connection and quietly dropped the rest while still saying "Thank you", which is why only three of Mark's eight test replies arrived. It now keeps up to thirty an hour, and anyone past that is told to try again rather than thanked.

### Changed

- Every field on the RSVP form must be filled in, the partner's too when a partner is coming.
- "Allergies" on the RSVP form now reads "Allergies (Please type details)".
- The partner question, the partner's details, the accessibility tick and the line about extra guests only show once "Will you be attending?" is answered Yes.

## [0.14.0] - 2026-11-11

### Added

- The plugin now installs its own updates. A new release reaches the site within about twelve hours without anyone pressing Update.

## [0.13.1] - 2026-11-11

### Changed

- A guest's username in the Users list is now their email address, not a made-up code. Guests already there are renamed to match. Anyone without an email of their own, such as a partner entered with somebody else's address, keeps a made-up one.

## [0.13.0] - 2026-11-11

### Changed

- The RSVP form calls the plus-one a "partner", not a "guest".
- The accessibility tick sits a little closer to the fields above it.

### Added

- A line above Send tells anyone bringing more than one extra guest to email info@brodfest.net.

## [0.12.3] - 2026-11-10

### Fixed

- The automated checks pass again: the design measurements expect the shorter TRANSPORT heading on phones, and letters are placed the same way on the check machine as on a desktop.

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
