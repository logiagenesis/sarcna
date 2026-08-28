-- ============================================================================
--  SARCNA 2027 Convention — core seed
--  Settings, email templates and the legal / policy pages.
--  This is NOT demo content: it is the minimum the site needs to run.
-- ============================================================================

SET NAMES utf8mb4;

-- --------------------------------------------------------------- settings --

INSERT INTO `settings` (`group_name`, `key_name`, `value`, `type`, `label`, `description`, `options`, `sort_order`) VALUES
('general','site_name','SARCNA 2027 Convention','text','Website name','Shown in the browser tab, emails and structured data.',NULL,1),
('general','default_meta_description','SARCNA 2027 Convention — Rooted in Recovery. Rising Together. 27–29 August 2027 at Boschendal Retreat Cottages & Conference Venue in the Cape Winelands.','textarea','Default meta description','Used on any page that does not set its own.',NULL,2),
('general','event_slogan','Rooted in Recovery. Rising Together.','text','Slogan','',NULL,3),
('general','event_dates_label','27–29 August 2027','text','Dates label','Human-readable dates shown across the site.',NULL,4),
('general','event_venue_name','Boschendal Retreat Cottages & Conference Venue','text','Venue name','',NULL,5),
('general','mock_data_banner','1','boolean','Show the committee preview banner','Turn this off before the site goes public.',NULL,6),
('general','maintenance_mode','0','boolean','Maintenance mode','Shows a holding page to visitors who are not signed in as admin.',NULL,7),
('general','show_countdown','1','boolean','Show the countdown timer','',NULL,8),

('contact','contact_email','info@sarcna.org.za','email','Public contact email','Shown in the footer and used as the reply-to.',NULL,1),
('contact','contact_phone','','text','Public phone number','',NULL,2),
('contact','admin_notification_email','info@sarcna.org.za','email','Order notification email','Where new orders and failed payments are reported.',NULL,3),
('contact','registration_email','','email','Registration enquiries email','',NULL,4),
('contact','accommodation_email','','email','Accommodation enquiries email','',NULL,5),
('contact','transport_email','','email','Transport enquiries email','',NULL,6),
('contact','postal_address','','textarea','Postal address','',NULL,7),

('whatsapp','whatsapp_enabled','1','boolean','Show the floating WhatsApp button','',NULL,1),
('whatsapp','whatsapp_number','','text','WhatsApp number','International format, digits only, e.g. 27821234567.',NULL,2),
('whatsapp','whatsapp_message','Hi SARCNA 2027, I need help with registration/accommodation/transport.','textarea','Pre-filled message','',NULL,3),
('whatsapp','whatsapp_hide_on_checkout','1','boolean','Minimise the button during checkout','Keeps the button out of the way of the payment buttons.',NULL,4),

('analytics','ga_measurement_id','','text','GA4 measurement ID','Looks like G-XXXXXXXXXX.',NULL,1),
('analytics','google_site_verification','','text','Search Console verification code','The content value from the HTML tag method.',NULL,2),
('analytics','cookie_notice_enabled','1','boolean','Show the cookie notice','',NULL,3),

('shop','shop_enabled','1','boolean','Shop is open','',NULL,1),
('shop','registration_open','1','boolean','Registration is open','',NULL,2),
('shop','accommodation_enabled','1','boolean','Accommodation booking is open','',NULL,3),
('shop','transport_enabled','1','boolean','Transport booking is open','',NULL,4),
('shop','donations_enabled','1','boolean','Donations are open','',NULL,5),
('shop','booking_hold_minutes','15','number','Bed hold length (minutes)','How long a bed stays reserved in a cart before it is released.',NULL,6),
('shop','accommodation_nights','2027-08-26,2027-08-27,2027-08-28','text','Bookable nights','Comma-separated dates, YYYY-MM-DD.',NULL,7),
('shop','merch_pickup_note','All merchandise is collected at the registration desk during the convention weekend.','textarea','Merchandise collection note','',NULL,8),
('shop','order_terms_note','Registration, accommodation and transport are confirmed only once PayFast has verified your payment.','textarea','Checkout note','',NULL,9),

('accounts','require_email_verification','0','boolean','Require email verification before checkout','',NULL,1),
('accounts','allow_guest_checkout','0','boolean','Allow checkout without an account','Attendee records are easier to manage when everyone has an account.',NULL,2),

('payments','payfast_skip_ip_check','0','boolean','Skip the PayFast source-IP check','Only enable temporarily if your host cannot resolve PayFast DNS. The signature check always stays on.',NULL,1);

-- -------------------------------------------------------- email templates --
-- Bodies live in app/Views/emails; only the subject line is edited here.

INSERT INTO `email_templates` (`key_name`, `name`, `subject`, `body_html`, `variables`) VALUES
('account_verification','Email verification','Confirm your email for SARCNA 2027','Rendered from app/Views/emails/verify-email.php','{first_name}, {link}'),
('password_reset','Password reset','Reset your SARCNA 2027 password','Rendered from app/Views/emails/password-reset.php','{first_name}, {link}'),
('welcome','Welcome','Welcome to SARCNA 2027','Rendered from app/Views/emails/welcome.php','{first_name}'),
('order_confirmation','Order created','Your SARCNA 2027 order {reference}','Rendered from app/Views/emails/order-created.php','{reference}, {total}'),
('payment_received','Payment received','Payment received — SARCNA 2027 order {reference}','Rendered from app/Views/emails/order-paid.php','{reference}, {total}, {checkin_code}'),
('payment_failed','Payment failed','We could not process your payment — order {reference}','Rendered from app/Views/emails/payment-failed.php','{reference}'),
('accommodation_confirmation','Accommodation confirmed','Your SARCNA 2027 accommodation is confirmed','Included in the payment received email.','{reference}, {room_type}, {nights}'),
('transport_confirmation','Transport confirmed','Your SARCNA 2027 transport is confirmed','Included in the payment received email.','{reference}, {route}, {departs_at}'),
('service_application','Service application received','We received your SARCNA 2027 service application','Rendered from app/Views/emails/service-application.php','{name}, {reference}'),
('contact_received','Contact form received','Thank you for contacting SARCNA 2027','Rendered from app/Views/emails/contact-received.php','{name}, {subject}'),
('donation_received','Donation received','Thank you for supporting SARCNA 2027','Rendered from app/Views/emails/donation-received.php','{amount}, {reference}'),
('admin_new_order','Admin: new order','New paid order {reference}','Rendered from app/Views/emails/admin-new-order.php','{reference}, {total}'),
('admin_low_stock','Admin: low stock','Low stock warning','Rendered from app/Views/emails/admin-low-stock.php','{products}'),
('admin_failed_payment','Admin: failed payment','Failed payment on order {reference}','Rendered from app/Views/emails/admin-failed-payment.php','{reference}, {reason}');

-- ------------------------------------------------------------ legal pages --
-- Draft copy. The committee must have these reviewed before the site is public.

INSERT INTO `pages` (`slug`, `title`, `subtitle`, `hero_image`, `meta_description`, `is_legal`, `body_html`) VALUES
('privacy-policy','Privacy Policy','How we collect, use and protect your personal information','/assets/img/backgrounds/section-mist.jpg','How the SARCNA 2027 Convention collects, uses and protects your personal information, in line with POPIA.',1,
'<p><em>Draft for committee review. Last updated on installation.</em></p>
<h2>Who we are</h2>
<p>This website is operated by the SARCNA 2027 Convention Committee for the purpose of running the convention held from 27 to 29 August 2027 at Boschendal Retreat Cottages &amp; Conference Venue in the Cape Winelands.</p>
<h2>What we collect</h2>
<ul>
<li><strong>Account details</strong> — your name, email address and phone number.</li>
<li><strong>Booking details</strong> — the beds, shuttle seats and products you buy, plus guest and passenger names you supply.</li>
<li><strong>Accessibility and dietary notes</strong> — only where you choose to give them, so we can make the weekend work for you.</li>
<li><strong>Payment records</strong> — the amount, status and PayFast reference. <strong>We never see or store your card details.</strong> Those are handled entirely by PayFast.</li>
<li><strong>Technical data</strong> — your IP address and browser type in our server logs, and anonymised usage statistics in Google Analytics.</li>
</ul>
<h2>Why we collect it (POPIA)</h2>
<p>We process your information under the Protection of Personal Information Act, 2013. Our lawful bases are the performance of our agreement with you (your registration and bookings), our legitimate interest in running a safe convention, and your consent where you have given it.</p>
<h2>Anonymity</h2>
<p>We understand that anonymity is a spiritual foundation of our fellowship. We never publish attendee names, and we do not share attendee lists with anyone outside the committee. Where you supply a home group or clean time, it is used only for service placement.</p>
<h2>Who we share it with</h2>
<ul>
<li><strong>PayFast</strong> — to process your payment.</li>
<li><strong>The venue and transport providers</strong> — rooming lists and passenger manifests, limited to what they need to accommodate and move you.</li>
<li><strong>Our hosting provider</strong> — data is stored on servers located in South Africa.</li>
</ul>
<p>We do not sell your information, and we do not send marketing on behalf of anyone else.</p>
<h2>How long we keep it</h2>
<p>Order and payment records are kept for five years to meet South African financial record-keeping requirements. Account and booking details are deleted or anonymised within 24 months of the convention unless you ask us to keep them.</p>
<h2>Your rights</h2>
<p>You may ask us for a copy of your information, ask us to correct it, or ask us to delete it. Write to us using the contact details on our contact page and we will respond within 30 days. You may also complain to the Information Regulator (South Africa).</p>
<h2>Cookies</h2>
<p>We use a session cookie to keep you signed in and to remember your cart, and Google Analytics 4 with IP anonymisation to understand how the site is used. No advertising or cross-site tracking cookies are set.</p>'),

('terms','Terms and Conditions','The agreement between you and the convention committee','/assets/img/backgrounds/section-mist.jpg','Terms and conditions for registration, accommodation, transport and merchandise at the SARCNA 2027 Convention.',1,
'<p><em>Draft for committee review.</em></p>
<h2>1. These terms</h2>
<p>By registering, booking or purchasing on this website you agree to these terms. They are governed by South African law.</p>
<h2>2. Registration</h2>
<p>Registration secures your place at the convention for the dates shown on your confirmation. Registration is personal to you and may be transferred to another person only with the committee\'s written agreement.</p>
<h2>3. Prices and payment</h2>
<p>All prices are in South African Rand and include VAT where applicable. Your booking is confirmed only when PayFast has notified us that the payment succeeded. Arriving on the payment success page does not by itself confirm anything.</p>
<h2>4. Accommodation</h2>
<p>Accommodation is sold per bed per night. Booking one bed in a shared room does not reserve the other beds; you may be sharing with other attendees. Where you book a private unit, every bed in that unit is reserved for you. Room allocations may be adjusted by the committee for accessibility or operational reasons; we will tell you if that happens.</p>
<h2>5. Transport</h2>
<p>Shuttle seats are sold per passenger per route and departure time. Please arrive at the pick-up point 15 minutes before departure. We cannot hold a shuttle for late passengers or delayed flights, though we will do our best to move you to a later departure where seats allow.</p>
<h2>6. Merchandise</h2>
<p>Merchandise is collected at the registration desk during the convention weekend unless delivery is offered at checkout. Sizes and colours are subject to availability.</p>
<h2>7. Behaviour</h2>
<p>Attendance is subject to our <a href="/code-of-conduct">Code of Conduct</a>. The committee may ask anyone who breaches it to leave, without a refund.</p>
<h2>8. Changes and cancellation by us</h2>
<p>If the convention has to be moved or cancelled for reasons beyond our control, we will refund fees for services not delivered, less any costs the committee cannot recover. We are not liable for your travel or other arrangements.</p>
<h2>9. Liability</h2>
<p>You attend and use the venue at your own risk. Nothing in these terms excludes liability that cannot be excluded under South African law.</p>
<h2>10. Contact</h2>
<p>Questions about these terms can be sent through our contact page.</p>'),

('refund-policy','Refund Policy','Cancellations, transfers and refunds','/assets/img/backgrounds/section-mist.jpg','Cancellation and refund terms for SARCNA 2027 registration, accommodation, transport and merchandise.',1,
'<p><em>Draft for committee review. The committee must confirm the dates and percentages below against the venue contract.</em></p>
<h2>How to cancel</h2>
<p>Email the committee from the address on your booking, quoting your order reference. Cancellations are effective on the date we receive them.</p>
<h2>Registration</h2>
<table>
<tr><th>When you cancel</th><th>Refund</th></tr>
<tr><td>More than 60 days before the convention</td><td>100% less a R100 administration fee</td></tr>
<tr><td>30–60 days before</td><td>50%</td></tr>
<tr><td>Less than 30 days before</td><td>No refund, but you may transfer your registration to another person</td></tr>
</table>
<h2>Accommodation</h2>
<p>Accommodation refunds follow the venue\'s own cancellation terms. As a rule: full refund more than 45 days before arrival, 50% between 14 and 45 days, and no refund within 14 days. Where the committee can resell your bed, we will refund you in full less any bank charges.</p>
<h2>Transport</h2>
<p>Shuttle seats are refundable in full up to 14 days before departure, and are non-refundable after that because the vehicles are booked and paid for in advance.</p>
<h2>Merchandise</h2>
<p>Unworn, unused merchandise may be returned at the registration desk during the weekend for an exchange or refund. Personalised items cannot be returned.</p>
<h2>Donations</h2>
<p>Donations are non-refundable.</p>
<h2>How refunds are paid</h2>
<p>Refunds are paid back to the original payment method through PayFast, normally within 10 working days of approval.</p>'),

('code-of-conduct','Convention Code of Conduct','How we keep the weekend safe for everyone','/assets/img/backgrounds/section-mist.jpg','The code of conduct that applies to everyone attending the SARCNA 2027 Convention.',1,
'<h2>Our promise to each other</h2>
<p>This convention exists so that anyone can find recovery, fellowship and rest. Everything below follows from that.</p>
<h2>What we expect</h2>
<ul>
<li><strong>Respect anonymity.</strong> Who you see here and what you hear here stays here.</li>
<li><strong>No photographs of people without their consent.</strong> See our <a href="/photo-anonymity-notice">photo and anonymity notice</a>.</li>
<li><strong>The convention is a substance-free space.</strong> Alcohol and other drugs are not part of any convention activity, session or social event.</li>
<li><strong>Treat every person with dignity</strong> regardless of age, race, gender, sexual orientation, disability, religion, clean time or home group.</li>
<li><strong>No harassment</strong> of any kind — including unwanted attention, intimidation, sexual harassment or predatory behaviour.</li>
<li><strong>Look after the venue.</strong> It is a working farm estate shared with other guests.</li>
<li><strong>Respect quiet hours</strong> in the accommodation areas from 23:00.</li>
</ul>
<h2>Newcomers and vulnerable attendees</h2>
<p>Newcomers are the most important people here. Anyone attempting to exploit a newcomer will be asked to leave immediately.</p>
<h2>If something goes wrong</h2>
<p>Speak to any committee member, or go to the registration desk, at any hour. You will be heard, and you can ask for a woman or a man to be present. Serious matters will be referred to the appropriate authorities.</p>
<h2>Consequences</h2>
<p>The committee may issue a warning, restrict access to parts of the programme, or require someone to leave the convention without a refund.</p>'),

('photo-anonymity-notice','Photo &amp; Anonymity Notice','Photography, video and social media at the convention','/assets/img/backgrounds/section-mist.jpg','How photography, video and social media are handled at the SARCNA 2027 Convention to protect anonymity.',1,
'<h2>Anonymity comes first</h2>
<p>Anonymity is the spiritual foundation of our traditions. Please treat it as more important than any photograph.</p>
<h2>The rules</h2>
<ul>
<li>Do not photograph or film anyone without asking them first.</li>
<li>No photography or filming at all inside meetings, workshops or the fellowship lounge.</li>
<li>Do not post images of other attendees on social media, even in the background, unless every recognisable person has agreed.</li>
<li>Official photography happens only in clearly signposted areas, by photographers wearing a committee badge.</li>
</ul>
<h2>Official photographs</h2>
<p>Where the committee takes photographs for future convention material, we will tell you at the time, and we will always offer a way to opt out. If you appear in a photograph we have published and want it removed, contact us and we will remove it.</p>
<h2>Wristbands</h2>
<p>At registration you may take a coloured wristband that signals "please do not photograph me". Committee members and official photographers respect it without question.</p>'),

('accommodation-terms','Accommodation Booking Terms','Beds, rooms, sharing and arrival','/assets/img/backgrounds/section-mist.jpg','Booking terms for accommodation at the SARCNA 2027 Convention, including bed-level bookings and private units.',1,
'<h2>How beds are sold</h2>
<p>Accommodation is sold <strong>per bed, per night</strong>. If you book one bed in a two-bed cottage, the other bed remains on sale and may be booked by another attendee. If you would rather not share, choose the <strong>private unit</strong> option, which reserves every bed in that unit for you.</p>
<h2>Sharing requests</h2>
<p>You can name someone you would like to share with when you book. We do our best to honour requests but cannot guarantee them; both people need to book the same room type and nights.</p>
<h2>Nights</h2>
<p>The convention runs from Friday 27 to Sunday 29 August 2027. Thursday 26 August is available as an optional early-arrival night where the room type allows it.</p>
<h2>Check-in and check-out</h2>
<p>Check-in from 16:00 on your first night. Check-out by 10:00 on the morning of departure; luggage can be left at the registration desk until the afternoon shuttles.</p>
<h2>Accessibility</h2>
<p>Accessible units are held for attendees who need them. Please describe your requirements in the booking notes so we can allocate the right unit.</p>
<h2>Damage</h2>
<p>The venue may charge for damage beyond normal wear. Please treat the cottages as you would a friend\'s home.</p>
<h2>Cancellation</h2>
<p>See our <a href="/refund-policy">refund policy</a>.</p>'),

('transport-terms','Transport Terms','Shuttles, pick-ups and luggage','/assets/img/backgrounds/section-mist.jpg','Terms for shuttle and transport bookings at the SARCNA 2027 Convention.',1,
'<h2>Seats</h2>
<p>Every shuttle seat is booked for a named passenger, a route and a departure time. Seats are limited by the vehicle capacity shown when you book.</p>
<h2>Arriving on time</h2>
<p>Be at the pick-up point 15 minutes before departure. Shuttles run to a schedule that serves the whole convention and cannot wait.</p>
<h2>Flights</h2>
<p>For airport routes, give us your flight number when you book. If your flight is delayed, WhatsApp the transport co-ordinator as early as you can and we will try to move you to a later shuttle, subject to seats.</p>
<h2>Luggage</h2>
<p>One suitcase and one small bag per passenger. Tell us in advance if you have more, or a wheelchair, mobility aid or other equipment.</p>
<h2>Accessibility</h2>
<p>Note any accessibility needs when you book so we can allocate a suitable vehicle.</p>
<h2>Changes and refunds</h2>
<p>See our <a href="/refund-policy">refund policy</a>. Route and time changes are free up to 14 days before departure, subject to availability.</p>'),

('merchandise-terms','Merchandise Terms','Ordering, collection and returns','/assets/img/backgrounds/section-mist.jpg','Terms for merchandise ordered through the SARCNA 2027 Convention shop.',1,
'<h2>Collection</h2>
<p>Merchandise is collected at the registration desk during the convention weekend. Bring your order reference or check-in code.</p>
<h2>Stock</h2>
<p>Sizes and colours are limited and sold on a first-come basis. If an item sells out after you order, we will contact you to arrange an alternative or a refund.</p>
<h2>Quality</h2>
<p>If an item is faulty, bring it back to the registration desk and we will replace it or refund it.</p>
<h2>Returns</h2>
<p>Unworn, unused items may be returned during the weekend. Personalised items cannot be returned.</p>
<h2>Delivery</h2>
<p>Delivery is not offered by default. If the committee enables delivery, courier charges and timelines are shown at checkout.</p>');

-- --------------------------------------------------------- expense categories --
-- The buckets a convention treasurer actually reports against.

INSERT INTO `expense_categories` (`name`, `slug`, `sort_order`) VALUES
('Venue & accommodation','venue-accommodation',1),
('Catering','catering',2),
('Transport & shuttles','transport',3),
('Merchandise stock','merchandise',4),
('Programme & speakers','programme',5),
('Equipment & AV','equipment-av',6),
('Printing & signage','printing-signage',7),
('Website & software','website-software',8),
('Banking & payment fees','banking-fees',9),
('Scholarships & sponsorships','scholarships',10),
('Admin & sundries','admin-sundries',11);

-- Finance settings.
INSERT INTO `settings` (`group_name`, `key_name`, `value`, `type`, `label`, `description`, `options`, `sort_order`) VALUES
('finance','vat_registered','0','boolean','The convention is VAT registered','Turn on only if the committee is registered for VAT. It adds a VAT view to the finance reports.',NULL,1),
('finance','vat_rate','15','number','VAT rate (%)','South African standard rate. Used only when VAT registered is on.',NULL,2),
('finance','financial_year_start','2026-09-01','text','Financial year starts','YYYY-MM-DD. Used as the default reporting period.',NULL,3),
('finance','payfast_fee_percent','3.5','text','PayFast fee (%)','Used to estimate fees on orders where PayFast has not reported the actual fee.',NULL,4),
('finance','payfast_fee_fixed','2.00','text','PayFast fee, fixed portion (R)','Added to the percentage when estimating fees.',NULL,5),
('finance','treasurer_email','','email','Treasurer email','Where the finance pack is addressed.',NULL,6);
