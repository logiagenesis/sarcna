-- ============================================================================
--  SARCNA 2027 Convention — demo content
--
--  Everything here is MOCK DATA for the committee preview and is flagged with
--  is_mock = 1 so it is obvious in the admin. Replace with confirmed detail
--  before launch. Prices are in CENTS.
-- ============================================================================

SET NAMES utf8mb4;

-- ------------------------------------------------------------- categories --

INSERT INTO `product_categories` (`id`, `name`, `slug`, `description`, `sort_order`) VALUES
(1,'Convention Registration','registration','Full weekend registration and single day passes.',1),
(2,'Accommodation','accommodation','Beds and private units at the venue.',2),
(3,'Merchandise','merchandise','Convention clothing and keepsakes, collected at the registration desk.',3),
(4,'Transport','transport','Airport, city and local shuttle seats.',4),
(5,'Donations','donations','Seventh Tradition and sponsorship contributions.',5),
(6,'Service &amp; Fundraising','service-fundraising','Fundraising items supporting the convention.',6);

-- --------------------------------------------------------------- products --

INSERT INTO `products` (`id`,`category_id`,`type`,`name`,`slug`,`sku`,`short_description`,`description`,`price_cents`,`sale_price_cents`,`sale_ends_at`,`track_stock`,`stock`,`low_stock_threshold`,`max_per_order`,`requires_attendee`,`pickup_only`,`image`,`meta_description`,`is_featured`,`is_mock`,`sort_order`) VALUES
(1,1,'registration','Full Weekend Registration — Early Bird','full-weekend-early-bird','REG-EB','Everything from Friday afternoon to Sunday lunch, at the early-bird rate.','<p>Your early-bird registration covers the full convention weekend: all meetings, workshops, speaker sessions, the Saturday night celebration and the Sunday closing meeting.</p><p>Early-bird pricing is limited and closes once the allocation is taken. Registration does not include accommodation, transport or meals other than those listed in the programme.</p>',85000,NULL,NULL,1,300,25,6,1,1,'/assets/img/backgrounds/cta-sunset.jpg','Early-bird registration for the SARCNA 2027 Convention, 27–29 August 2027 at Boschendal.',1,1,1),
(2,1,'registration','Full Weekend Registration — Standard','full-weekend-standard','REG-STD','The full convention weekend at the standard rate.','<p>Standard registration for the whole weekend — every meeting, workshop, speaker session and the Saturday night celebration.</p><p>Accommodation and transport are booked separately so you can choose what suits you.</p>',95000,NULL,NULL,1,500,25,6,1,1,'/assets/img/backgrounds/cta-sunset.jpg','Standard full weekend registration for the SARCNA 2027 Convention.',1,1,2),
(3,1,'day_pass','Friday Day Pass','friday-day-pass','REG-FRI','Friday afternoon and evening, including the opening meeting.','<p>Covers Friday from registration at 14:00 through the welcome fellowship and the opening meeting.</p>',25000,NULL,NULL,1,150,15,6,1,1,'/assets/img/venue/boma-firepit.jpg','Friday day pass for the SARCNA 2027 Convention.',0,1,3),
(4,1,'day_pass','Saturday Day Pass','saturday-day-pass','REG-SAT','The full Saturday programme, including the evening celebration.','<p>The busiest day of the weekend: the main meeting, workshops, speaker sessions, the service forum and the Saturday night celebration.</p>',45000,NULL,NULL,1,200,15,6,1,1,'/assets/img/venue/auditorium.jpg','Saturday day pass for the SARCNA 2027 Convention.',1,1,4),
(5,1,'day_pass','Sunday Day Pass','sunday-day-pass','REG-SUN','Sunday morning meditation, breakfast and the closing meeting.','<p>A gentle final morning: meditation, spiritual breakfast and the closing meeting.</p>',25000,NULL,NULL,1,150,15,6,1,1,'/assets/img/venue/fynbos-gardens.jpg','Sunday day pass for the SARCNA 2027 Convention.',0,1,5),

(6,3,'merchandise','SARCNA 2027 T-Shirt','sarcna-2027-t-shirt','MERCH-TEE','Soft cotton tee with the Cape Winelands badge.','<p>A comfortable cotton t-shirt carrying the SARCNA 2027 badge and the convention slogan on the back.</p><p>Collected at the registration desk during the weekend.</p>',25000,NULL,NULL,1,240,20,5,0,1,'/assets/img/merch/t-shirt.jpg','Official SARCNA 2027 Convention t-shirt.',1,1,10),
(7,3,'merchandise','SARCNA 2027 Hoodie','sarcna-2027-hoodie','MERCH-HOOD','Warm brushed-fleece hoodie for cool Winelands evenings.','<p>The Cape Winelands can turn cold after sunset. This brushed-fleece hoodie carries the badge on the chest and the slogan across the shoulders.</p>',55000,NULL,NULL,1,150,15,3,0,1,'/assets/img/merch/hoodie.jpg','Official SARCNA 2027 Convention hoodie.',1,1,11),
(8,3,'merchandise','SARCNA 2027 Cap','sarcna-2027-cap','MERCH-CAP','Six-panel cap with an embroidered badge.','<p>An embroidered six-panel cap in clay or vineyard green, with an adjustable strap.</p>',18000,NULL,NULL,1,180,15,4,0,1,'/assets/img/merch/cap.jpg','Official SARCNA 2027 Convention cap.',0,1,12),
(9,3,'merchandise','SARCNA 2027 Tote Bag','sarcna-2027-tote-bag','MERCH-TOTE','Heavy cotton tote for the weekend.','<p>A sturdy cotton tote, big enough for a workbook, a water bottle and a jersey.</p>',14000,NULL,NULL,1,220,20,5,0,1,'/assets/img/merch/tote-bag.jpg','Official SARCNA 2027 Convention tote bag.',0,1,13),
(10,3,'merchandise','SARCNA 2027 Mug','sarcna-2027-mug','MERCH-MUG','Ceramic mug for the coffee that keeps us going.','<p>A generous ceramic mug with the badge on one side and the slogan on the other.</p>',12000,NULL,NULL,1,200,20,6,0,1,'/assets/img/merch/mug.jpg','Official SARCNA 2027 Convention mug.',0,1,14),
(11,3,'merchandise','Sticker Pack','sarcna-2027-sticker-pack','MERCH-STICK','Six vinyl stickers for laptops, water bottles and journals.','<p>Six weatherproof vinyl stickers featuring the badge, the mountains and the slogan.</p>',5000,NULL,NULL,1,400,30,10,0,1,'/assets/img/merch/stickers.jpg','SARCNA 2027 Convention sticker pack.',0,1,15),
(12,3,'merchandise','Convention Lanyard','sarcna-2027-lanyard','MERCH-LAN','Woven lanyard and badge holder for the weekend.','<p>A woven lanyard with a badge holder — handy for your registration card and room key.</p>',8000,NULL,NULL,1,350,30,6,0,1,'/assets/img/merch/lanyard.jpg','SARCNA 2027 Convention lanyard.',0,1,16),

(13,5,'donation','7th Tradition Donation','7th-tradition-donation','DON-7TH','Support the convention with any amount.','<p>Every convention is self-supporting through the contributions of the fellowship. Give whatever you can.</p>',0,NULL,NULL,0,0,0,1,0,0,'/assets/img/backgrounds/section-mist.jpg','Make a Seventh Tradition donation to the SARCNA 2027 Convention.',1,1,20),
(14,5,'donation','Sponsor a Newcomer Registration','sponsor-a-newcomer','DON-NEW','Cover one full weekend registration for someone who cannot afford it.','<p>Your R850 pays for one newcomer to attend the whole weekend. The committee allocates sponsorships confidentially.</p>',85000,NULL,NULL,0,0,0,10,0,0,'/assets/img/venue/boma-firepit.jpg','Sponsor a newcomer registration at the SARCNA 2027 Convention.',1,1,21),
(15,5,'donation','Sponsor a Bed','sponsor-a-bed','DON-BED','Help someone stay for the weekend.','<p>Contribute any amount towards accommodation for attendees who could not otherwise stay over.</p>',0,NULL,NULL,0,0,0,1,0,0,'/assets/img/rooms/retreat-twin-room.jpg','Sponsor a bed at the SARCNA 2027 Convention.',0,1,22);

UPDATE `products` SET `allows_custom_amount` = 1, `min_amount_cents` = 2000 WHERE `id` IN (13, 15);

-- ---------------------------------------------------------------- variants --

INSERT INTO `product_variants` (`product_id`,`size`,`colour`,`sku`,`price_delta_cents`,`stock`,`sort_order`) VALUES
(6,'S','Vineyard Green','TEE-S-VG',0,30,1),(6,'M','Vineyard Green','TEE-M-VG',0,45,2),(6,'L','Vineyard Green','TEE-L-VG',0,45,3),(6,'XL','Vineyard Green','TEE-XL-VG',0,30,4),(6,'2XL','Vineyard Green','TEE-2XL-VG',3000,15,5),
(6,'S','Warm Cream','TEE-S-WC',0,15,6),(6,'M','Warm Cream','TEE-M-WC',0,20,7),(6,'L','Warm Cream','TEE-L-WC',0,20,8),(6,'XL','Warm Cream','TEE-XL-WC',0,15,9),(6,'2XL','Warm Cream','TEE-2XL-WC',3000,5,10),
(7,'S','Deep Forest','HOOD-S-DF',0,15,1),(7,'M','Deep Forest','HOOD-M-DF',0,30,2),(7,'L','Deep Forest','HOOD-L-DF',0,35,3),(7,'XL','Deep Forest','HOOD-XL-DF',0,25,4),(7,'2XL','Deep Forest','HOOD-2XL-DF',5000,10,5),
(7,'M','Clay','HOOD-M-CL',0,12,6),(7,'L','Clay','HOOD-L-CL',0,15,7),(7,'XL','Clay','HOOD-XL-CL',0,8,8),
(8,'One size','Clay','CAP-OS-CL',0,90,1),(8,'One size','Vineyard Green','CAP-OS-VG',0,90,2);

INSERT INTO `product_images` (`product_id`,`file_path`,`alt_text`,`source_note`,`sort_order`) VALUES
(6,'/assets/img/merch/t-shirt.jpg','Mock-up of the SARCNA 2027 convention t-shirt','Original illustration generated for this mockup',1),
(7,'/assets/img/merch/hoodie.jpg','Mock-up of the SARCNA 2027 convention hoodie','Original illustration generated for this mockup',1),
(8,'/assets/img/merch/cap.jpg','Mock-up of the SARCNA 2027 convention cap','Original illustration generated for this mockup',1),
(9,'/assets/img/merch/tote-bag.jpg','Mock-up of the SARCNA 2027 convention tote bag','Original illustration generated for this mockup',1),
(10,'/assets/img/merch/mug.jpg','Mock-up of the SARCNA 2027 convention mug','Original illustration generated for this mockup',1),
(11,'/assets/img/merch/stickers.jpg','Mock-up of the SARCNA 2027 sticker pack','Original illustration generated for this mockup',1),
(12,'/assets/img/merch/lanyard.jpg','Mock-up of the SARCNA 2027 lanyard','Original illustration generated for this mockup',1);

-- ------------------------------------------------------------- room types --
-- Modelled on the real Boschendal Retreat: 18 self-catering cottages, each with
-- TWO bedrooms, each bedroom having its own private exterior entrance and
-- en-suite bathroom. That is 36 lettable bedrooms and 72 beds — the venue's own
-- published capacity of "72 guests sharing".
--
-- The sellable unit is therefore the BEDROOM, not the cottage. Two beds per
-- bedroom, sold individually, with a private-room buyout for anyone who would
-- rather not share. Cottages are shared between two bedrooms only, and each
-- bedroom is entered from outside, so sharing a cottage is not sharing a room.

INSERT INTO `room_types` (`id`,`name`,`slug`,`summary`,`description`,`beds_per_unit`,`bed_rate_cents`,`private_unit_rate_cents`,`allows_private_buyout`,`is_accessible`,`is_offsite`,`amenities`,`hero_image`,`meta_title`,`meta_description`,`sort_order`,`is_mock`) VALUES
(1,'Retreat Cottage Twin Room','retreat-cottage-twin-room','An en-suite twin room in a Retreat cottage, with its own front door onto the lawns.','<p>The Boschendal Retreat is a cluster of eighteen self-catering cottages set across manicured lawns in a secluded corner of the 1&nbsp;800-hectare farm, with the Simonsberg on one side and the Groot Drakenstein on the other.</p><p>Each cottage holds <strong>two twin bedrooms</strong>. Every bedroom has <strong>its own private exterior entrance</strong> and <strong>its own en-suite bathroom</strong> with a shower over the bath, so you are never walking through anyone else&rsquo;s room. The two bedrooms share the cottage&rsquo;s living room, kitchen and fireplace, and a private patio with a braai.</p><p>Book one bed and share the room with another attendee, or take the whole room privately.</p>',2,145000,260000,1,0,0,'Two single beds|Private exterior entrance|En-suite bathroom with shower over bath|Shared cottage living room and kitchen|Indoor fireplace|Private patio and braai|WiFi and satellite TV|Heating|Linen and towels supplied','/assets/img/rooms/retreat-twin-room.jpg','Retreat Cottage Twin Room','En-suite twin rooms at the Boschendal Retreat for the SARCNA 2027 Convention, from R1 450 per bed per night.',1,1),
(2,'Retreat Cottage Accessible Room','retreat-cottage-accessible-room','A step-free en-suite twin room in the cottages closest to the auditorium.','<p>The same Retreat cottage bedroom, in the units closest to the auditorium and the dining lounge, on level paved paths.</p><p>Step-free from the parking bay to the bed, a widened doorway, a roll-in shower and grab rails. Each room keeps its own private exterior entrance and en-suite bathroom.</p><p>Please describe your requirements in the booking notes so the accommodation team can allocate the right room and confirm the detail with the venue.</p>',2,145000,260000,1,1,0,'Two single beds|Step-free access throughout|Roll-in shower and grab rails|Widened doorway|Private exterior entrance|En-suite bathroom|Closest to the auditorium|Accessible parking bay|WiFi and satellite TV|Heating','/assets/img/rooms/retreat-accessible-room.jpg','Accessible Retreat Cottage Room','Step-free en-suite accommodation at the Boschendal Retreat for the SARCNA 2027 Convention.',2,1),
(3,'Partner Guest House — Franschhoek','partner-guest-house-franschhoek','Twin rooms fifteen minutes away, with a free shuttle to every session.','<p>The Retreat sleeps seventy-two, and the convention is bigger than that. Our overflow partner guest house in Franschhoek holds the rest, fifteen minutes down the valley.</p><p>Twin rooms with en-suite bathrooms and breakfast included. A free shuttle runs to the Retreat before every session and back afterwards, including late after the Saturday night celebration.</p><p><strong>This allocation is still being contracted.</strong> Numbers and rates are indicative until the committee confirms them.</p>',2,95000,170000,1,0,1,'Two single beds|En-suite bathroom|Breakfast included|Free shuttle to the Retreat|Secure parking|WiFi|Heating','/assets/img/rooms/partner-guest-house.jpg','Partner Guest House accommodation','Overflow accommodation in Franschhoek for the SARCNA 2027 Convention, from R950 per bed per night, with a free shuttle.',3,1);

INSERT INTO `room_type_images` (`room_type_id`,`file_path`,`alt_text`,`source_note`,`sort_order`) VALUES
(1,'/assets/img/rooms/retreat-twin-room.jpg','Illustration standing in for a Retreat cottage twin bedroom','PLACEHOLDER — replace with the venue media pack',1),
(1,'/assets/img/venue/retreat-cottages.jpg','Illustration of the Retreat cottages across the lawns','PLACEHOLDER — replace with the venue media pack',2),
(1,'/assets/img/venue/cottage-interior.jpg','Illustration standing in for a cottage living room and fireplace','PLACEHOLDER — replace with the venue media pack',3),
(2,'/assets/img/rooms/retreat-accessible-room.jpg','Illustration standing in for the step-free Retreat room','PLACEHOLDER — replace with the venue media pack, showing the roll-in shower',1),
(2,'/assets/img/venue/retreat-cottages.jpg','Illustration of the Retreat cottages across the lawns','PLACEHOLDER — replace with the venue media pack',2),
(3,'/assets/img/rooms/partner-guest-house.jpg','Illustration standing in for the partner guest house in Franschhoek','PLACEHOLDER — supply once the partner property is contracted',1);

-- Per-night rates: the Thursday early-arrival night is priced 10% lower.
INSERT INTO `bed_rates` (`room_type_id`,`night`,`bed_rate_cents`,`private_unit_rate_cents`,`is_available`,`label`) VALUES
(1,'2027-08-26',130500,234000,1,'Early arrival'),(1,'2027-08-27',145000,260000,1,NULL),(1,'2027-08-28',145000,260000,1,NULL),
(2,'2027-08-26',130500,234000,1,'Early arrival'),(2,'2027-08-27',145000,260000,1,NULL),(2,'2027-08-28',145000,260000,1,NULL),
(3,'2027-08-26', 85500,153000,1,'Early arrival'),(3,'2027-08-27', 95000,170000,1,NULL),(3,'2027-08-28', 95000,170000,1,NULL);

-- --------------------------------------------------------------- transport --

INSERT INTO `transport_routes` (`id`,`name`,`slug`,`description`,`direction`,`price_cents`,`requires_flight_number`,`sort_order`,`is_mock`) VALUES
(1,'Cape Town International Airport → Venue (one way)','airport-to-venue','<p>A direct shuttle from the airport to Boschendal, roughly one hour depending on traffic. Give us your flight number and we will match you to the closest departure.</p>','to_venue',35000,1,1,1),
(2,'Venue → Cape Town International Airport (one way)','venue-to-airport','<p>Sunday departures from the venue back to the airport, timed around the closing meeting and checkout.</p>','from_venue',35000,1,2,1),
(3,'Airport Return Shuttle','airport-return-shuttle','<p>Book both directions together and save. You choose your arrival and departure times at checkout.</p>','return',65000,1,3,1),
(4,'Cape Town CBD Return Shuttle','cape-town-cbd-return','<p>Pick-up in the city centre on Friday afternoon, returning on Sunday after the closing meeting.</p>','return',45000,0,4,1),
(5,'Stellenbosch Return Shuttle','stellenbosch-return','<p>A short hop from Stellenbosch, running on Friday and Sunday.</p>','return',25000,0,5,1),
(6,'Local Winelands Shuttle','local-winelands-shuttle','<p>Local pick-ups around Franschhoek and Paarl for attendees staying nearby.</p>','onsite',18000,0,6,1),
(7,'Day Visitor Parking Pass','day-visitor-parking-pass','<p>Free parking permit for day-pass attendees driving themselves. Book one per vehicle so the venue can plan the parking field.</p>','onsite',0,0,7,1);

INSERT INTO `transport_slots` (`route_id`,`departs_at`,`pickup_point`,`dropoff_point`,`capacity`,`notes`) VALUES
(1,'2027-08-26 14:00:00','CTIA Domestic Arrivals, bay 12','Boschendal registration desk',22,'Thursday early-arrival shuttle'),
(1,'2027-08-27 10:00:00','CTIA Domestic Arrivals, bay 12','Boschendal registration desk',22,NULL),
(1,'2027-08-27 13:00:00','CTIA Domestic Arrivals, bay 12','Boschendal registration desk',22,'Arrives in time for registration opening'),
(1,'2027-08-27 16:30:00','CTIA Domestic Arrivals, bay 12','Boschendal registration desk',22,'Last Friday arrival shuttle'),
(2,'2027-08-29 12:30:00','Boschendal registration desk','CTIA Departures',22,'Straight after the closing meeting'),
(2,'2027-08-29 15:00:00','Boschendal registration desk','CTIA Departures',22,'For later afternoon flights'),
(3,'2027-08-27 13:00:00','CTIA Domestic Arrivals, bay 12','Boschendal registration desk',22,'Return leg selected at checkout'),
(3,'2027-08-27 16:30:00','CTIA Domestic Arrivals, bay 12','Boschendal registration desk',22,'Return leg selected at checkout'),
(4,'2027-08-27 15:00:00','Cape Town Station, Adderley Street','Boschendal registration desk',18,'Returns Sunday at 13:00'),
(4,'2027-08-29 13:00:00','Boschendal registration desk','Cape Town Station, Adderley Street',18,'Return leg'),
(5,'2027-08-27 16:00:00','Stellenbosch Town Hall','Boschendal registration desk',14,NULL),
(5,'2027-08-29 13:00:00','Boschendal registration desk','Stellenbosch Town Hall',14,'Return leg'),
(6,'2027-08-27 17:00:00','Franschhoek village square','Boschendal registration desk',14,NULL),
(6,'2027-08-28 08:00:00','Franschhoek village square','Boschendal conference barn',14,'Saturday morning run'),
(6,'2027-08-29 08:30:00','Franschhoek village square','Boschendal conference barn',14,'Sunday morning run'),
(7,'2027-08-28 08:00:00','Boschendal main gate','Day visitor parking field',120,'Free permit — one per vehicle');

-- -------------------------------------------------------------- programme --
-- Locations use the Retreat's real spaces: the auditorium, the screening room,
-- the break-off boardrooms, the lounge and dining area, the boma and the
-- natural swimming pool.

INSERT INTO `programme_items` (`day_date`,`start_time`,`title`,`description`,`location`,`track`,`is_highlight`,`sort_order`) VALUES
('2027-08-27','14:00:00','Registration Opens','Collect your badge, your room key and your welcome pack.','Retreat reception','Logistics',0,1),
('2027-08-27','16:00:00','Cottage Check-in','Rooms open. Each bedroom has its own front door, so you can settle in quietly.','Retreat cottages','Logistics',0,2),
('2027-08-27','18:00:00','Welcome Fellowship','Meet the people you will share the weekend with, over something warm.','Lounge and dining area','Fellowship',0,3),
('2027-08-27','19:30:00','Opening Meeting','The convention opens with a speaker and the reading of the theme.','The auditorium','Main meeting',1,4),
('2027-08-27','21:00:00','Fireside Fellowship at the Boma','Late fellowship around the communal fire.','The boma','Fellowship',0,5),

('2027-08-28','07:00:00','Sunrise Trail Walk','A guided walk on the mountainside trails to watch the sun come over the Groot Drakenstein.','Meet at the Retreat reception','Wellness',0,1),
('2027-08-28','08:00:00','Breakfast &amp; Coffee','','Lounge and dining area','Meals',0,2),
('2027-08-28','09:00:00','Main Meeting','The Saturday main meeting, with the weekend keynote.','The auditorium','Main meeting',1,3),
('2027-08-28','11:00:00','Workshops','Concurrent workshops on sponsorship, service and the steps.','Break-off boardrooms','Workshops',0,4),
('2027-08-28','13:00:00','Lunch','','Lounge and dining area','Meals',0,5),
('2027-08-28','14:30:00','Speaker Sessions','Shared experience from speakers across the region.','The auditorium','Speakers',0,6),
('2027-08-28','14:30:00','Recovery Film &amp; Discussion','An alternative track in the Retreat''s private screening room.','The screening room','Workshops',0,7),
('2027-08-28','16:00:00','Service Forum','Open forum on convention and regional service.','Break-off boardroom 1','Service',0,8),
('2027-08-28','16:00:00','Quiet Hour at the Pool','The natural swimming pool and the fynbos gardens, for anyone who needs an hour off.','Natural swimming pool','Wellness',0,9),
('2027-08-28','18:00:00','Dinner','','Lounge and dining area','Meals',0,10),
('2027-08-28','20:00:00','Saturday Night Celebration','Music, dancing and the countdown — the highlight of the weekend.','The auditorium','Celebration',1,11),
('2027-08-28','22:00:00','Fellowship Lounge','A quieter room for talking late.','Lounge','Fellowship',0,12),

('2027-08-29','07:30:00','Morning Meditation','A quiet, guided start to the last day in the fynbos gardens.','Fynbos gardens','Wellness',0,1),
('2027-08-29','09:00:00','Spiritual Breakfast','Breakfast with a short shared reading.','Lounge and dining area','Fellowship',0,2),
('2027-08-29','10:30:00','Closing Meeting','The convention closes together.','The auditorium','Main meeting',1,3),
('2027-08-29','12:00:00','Checkout &amp; Transport Departures','Rooms vacated by 10:00; luggage can be left at reception.','Retreat reception','Logistics',0,4);

-- -------------------------------------------------------------------- FAQ --

INSERT INTO `faqs` (`category`,`question`,`answer`,`sort_order`) VALUES
('Registration','What does registration include?','<p>Registration covers every meeting, workshop and speaker session, the Saturday night celebration and your convention badge. Accommodation, transport and meals outside the programme are booked separately.</p>',1),
('Registration','Can I come for only one day?','<p>Yes. Friday, Saturday and Sunday day passes are on sale in the shop. Saturday is the fullest day if you can only choose one.</p>',2),
('Registration','Can I register someone else?','<p>Yes. Add a registration to your cart for each person and enter their details at checkout. Each attendee gets their own badge and check-in code.</p>',3),
('Accommodation','How does the accommodation actually work?','<p>The Boschendal Retreat is eighteen self-catering cottages. Each cottage has <strong>two bedrooms</strong>, and each bedroom has <strong>its own front door onto the lawn</strong> and <strong>its own en-suite bathroom</strong>. So a cottage is shared between two rooms, but you never walk through anyone else&rsquo;s space to reach yours.</p><p>We sell the <strong>bedroom</strong>, and within it we sell <strong>each bed</strong>. Thirty-six bedrooms, seventy-two beds.</p>',10),
('Accommodation','Can I book just one bed?','<p>Yes — that is exactly how it works. Beds are sold individually, so booking one bed in a twin room leaves the second bed on sale for someone else. If you would rather not share, choose the private room option and both beds are held for you.</p>',11),
('Accommodation','Can I choose who I share with?','<p>Add their name in the roommate request field when you book. We honour requests wherever we can, as long as you both book the same room type and the same nights.</p>',12),
('Accommodation','What is in the cottage?','<p>A living room with a kitchen and an indoor fireplace, shared between the two bedrooms, plus a private patio with a braai. WiFi and satellite TV throughout. Linen, towels and heating are included.</p>',13),
('Accommodation','What happens when the Retreat is full?','<p>The Retreat sleeps seventy-two. Beyond that we place people at a partner guest house in Franschhoek, fifteen minutes down the valley, with a free shuttle to every session and back — including late after the Saturday night celebration.</p>',14),
('Accommodation','Can I arrive on Thursday?','<p>Yes. Thursday 26 August is available as an optional early-arrival night, at a slightly lower rate.</p>',15),
('Accommodation','What if I need step-free access?','<p>Book an Accessible Retreat Room. Those are the cottages closest to the auditorium and the dining lounge, on level paved paths, with a widened doorway, a roll-in shower and grab rails. Describe your requirements in the booking notes and the accommodation team will confirm the detail with the venue before you arrive.</p>',16),
('Accommodation','How long is a bed held while I check out?','<p>Fifteen minutes. Once you add a bed to your cart it is held for you and no one else can buy it. If you do not finish paying in that time, the bed goes back on sale.</p>',17),
('Transport','How do the airport shuttles work?','<p>Choose your route and departure time in the shop, then give us your flight number. The transport co-ordinator matches passengers to vehicles and sends a WhatsApp with the meeting point the week before.</p>',20),
('Transport','What if my flight is delayed?','<p>Message the transport co-ordinator on WhatsApp as early as you can. We will move you to a later shuttle if there is a seat.</p>',21),
('Transport','Can I drive myself?','<p>Yes. The Retreat is about an hour from Cape Town and there is parking at the cottages. Day visitors should book the free parking pass so the venue can plan.</p>',22),
('Transport','Should I hire a car?','<p>If you want to explore the valley outside session times, a car is worth it — the Retreat is secluded and there is nothing within walking distance. If you are only coming for the convention, the shuttles cover everything. There is a car hire link on the checkout page.</p>',23),
('Payments','How do I pay?','<p>All payments go through PayFast, which accepts cards, Instant EFT, SnapScan and more. We never see your card details.</p>',30),
('Payments','When is my booking confirmed?','<p>The moment PayFast tells us the payment succeeded. You will get a confirmation email with your check-in code, your bed allocation and your shuttle details.</p>',31),
('Payments','Can I get a refund?','<p>Yes, within the windows set out in our refund policy. Registration is refundable up to 30 days before the convention, and beds follow the venue cancellation terms.</p>',32),
('The venue','Where exactly is the Retreat?','<p>In a secluded corner of the Boschendal farm — 1&nbsp;800 hectares between the Simonsberg and the Groot Drakenstein mountains, in the Dwars River valley between Franschhoek and Stellenbosch. About an hour from Cape Town.</p>',40),
('The venue','Is this a wine estate?','<p>Boschendal is a historic Cape farm with a wine-making history going back to title deeds of 1685. Our convention weekend is entirely substance-free: wine tasting is not part of the programme, is not offered at any convention event, and no alcohol is served at anything we run. The Retreat is a self-contained venue in its own corner of the farm.</p>',41),
('The venue','What is there to do between sessions?','<p>Mountainside walking trails, horse riding, a natural swimming pool, fynbos gardens, a communal boma that runs late, and a private screening room. The Retreat is quiet, green and easy to move around.</p>',42),
('The venue','What should I pack?','<p>Warm layers — Winelands nights in August are cold, and the cottages have fireplaces for a reason — comfortable walking shoes, and something smarter for the Saturday night celebration if you like. Swimming things if you are brave enough for the natural pool.</p>',43),
('Service','How do I do service at the convention?','<p>Fill in the service application form. The service co-ordinator will place you in an area and send you a shift.</p>',50),
('Anonymity','Can I take photographs?','<p>Only with the consent of everyone in the picture, and never inside meetings or the fellowship lounge. Please read our photo and anonymity notice before you post anything.</p>',60);

-- ----------------------------------------------------------------- banners --

INSERT INTO `banners` (`position`,`title`,`subtitle`,`body`,`image`,`image_alt`,`cta_label`,`cta_url`,`secondary_label`,`secondary_url`,`sort_order`,`is_mock`) VALUES
('home_hero','SARCNA 2027 Convention','Rooted in Recovery. Rising Together.','A weekend of fellowship, service, renewal, and connection in the Cape Winelands.','/assets/img/backgrounds/hero-winelands.jpg','Illustration of a Cape Winelands sunrise over vineyards and mountains','Register now','/shop/registration','Book accommodation','/accommodation',1,1),
('home_cta','Bring someone with you','Sponsor a newcomer registration','Every sponsored registration puts one more person in the room who could not otherwise be there.','/assets/img/backgrounds/cta-sunset.jpg','Illustration of the Winelands at sunset','Sponsor a newcomer','/donations','See all donations','/donations',1,1);

-- ---------------------------------------------------------------- gallery --
-- Every file below is a PLACEHOLDER illustration. Replace with the venue media
-- pack — see docs/image-source-log.md and tools/import-venue-images.php.

INSERT INTO `gallery_images` (`title`,`alt_text`,`file_path`,`category`,`source_note`,`sort_order`,`is_mock`) VALUES
('The farm at sunrise','Illustration of the Boschendal farm at sunrise, vineyard rows running to the mountains','/assets/img/venue/boschendal-overview.jpg','venue','PLACEHOLDER — replace with licensed venue photography',1,1),
('The Retreat cottages','Illustration of the Retreat cottages across the lawns','/assets/img/venue/retreat-cottages.jpg','venue','PLACEHOLDER — replace with licensed venue photography',2,1),
('Inside a cottage','Illustration standing in for a cottage living room and fireplace','/assets/img/venue/cottage-interior.jpg','rooms','PLACEHOLDER — replace with licensed venue photography',3,1),
('The auditorium','Illustration standing in for the Retreat auditorium','/assets/img/venue/auditorium.jpg','conference','PLACEHOLDER — replace with licensed venue photography',4,1),
('The screening room','Illustration standing in for the private screening room','/assets/img/venue/screening-room.jpg','conference','PLACEHOLDER — replace with licensed venue photography',5,1),
('Lounge and dining','Illustration standing in for the lounge and dining area','/assets/img/venue/dining-lounge.jpg','conference','PLACEHOLDER — replace with licensed venue photography',6,1),
('The boma','Illustration of the communal boma after sunset','/assets/img/venue/boma-firepit.jpg','venue','PLACEHOLDER — replace with licensed venue photography',7,1),
('The natural swimming pool','Illustration standing in for the natural swimming pool','/assets/img/venue/natural-pool.jpg','venue','PLACEHOLDER — replace with licensed venue photography',8,1),
('Fynbos gardens','Illustration of the fynbos gardens in morning mist','/assets/img/venue/fynbos-gardens.jpg','venue','PLACEHOLDER — replace with licensed venue photography',9,1),
('Mountainside trails','Illustration of the walking trails behind the Retreat','/assets/img/venue/walking-trails.jpg','venue','PLACEHOLDER — replace with licensed venue photography',10,1),
('Horse trails','Illustration of the riding trails across the farm','/assets/img/venue/horse-trails.jpg','venue','PLACEHOLDER — replace with licensed venue photography',11,1),
('The arrival drive','Illustration of the oak-lined arrival drive','/assets/img/venue/arrival-drive.jpg','venue','PLACEHOLDER — replace with licensed venue photography',12,1),
('A Retreat twin room','Illustration standing in for an en-suite twin bedroom','/assets/img/rooms/retreat-twin-room.jpg','rooms','PLACEHOLDER — replace with licensed venue photography',13,1),
('Winelands road','Illustration of the road through the valley','/assets/img/transport/winelands-road.jpg','transport','PLACEHOLDER — replace with licensed photography',14,1);

-- ------------------------------------------------------- upcoming events --

INSERT INTO `events` (`title`,`slug`,`description`,`starts_at`,`ends_at`,`location`,`image`,`link_url`,`is_mock`) VALUES
('Convention Fundraiser Braai','convention-fundraiser-braai','<p>A fundraiser braai in aid of the newcomer sponsorship fund. Bring your own meat, salads provided.</p>','2027-03-14 12:00:00','2027-03-14 17:00:00','Rondebosch Community Hall, Cape Town','/assets/img/venue/boma-firepit.jpg','/donations',1),
('Regional Service Workshop','regional-service-workshop','<p>An afternoon on convention service positions, open to anyone considering service in 2027.</p>','2027-05-08 14:00:00','2027-05-08 17:00:00','Stellenbosch','/assets/img/venue/screening-room.jpg','/service',1),
('Early Bird Registration Closes','early-bird-closes','<p>The last day to register at the early-bird rate.</p>','2027-06-30 23:59:00',NULL,'Online','/assets/img/backgrounds/cta-sunset.jpg','/shop/registration',1);

-- --------------------------------------------------------- test customer --
-- Password for both demo accounts: Convention2027
-- CHANGE OR DELETE THESE BEFORE THE SITE GOES LIVE.

INSERT INTO `users` (`first_name`,`last_name`,`email`,`phone`,`password_hash`,`home_group`,`region`,`is_admin`,`email_verified_at`,`is_mock`) VALUES
('Thandi','Mokoena','demo.customer@sarcna.org.za','+27 82 000 0001','$2y$12$z5/FE2KPVGWJGaKZzuzHHO6UAhyYOgVPAg/R1PckJgpFxt03nMER.','Sunrise Group','Western Cape',0,NOW(),1),
('Pieter','van Wyk','demo.volunteer@sarcna.org.za','+27 82 000 0002','$2y$12$z5/FE2KPVGWJGaKZzuzHHO6UAhyYOgVPAg/R1PckJgpFxt03nMER.','Freedom Group','Western Cape',0,NOW(),1);

INSERT INTO `service_applications` (`reference`,`name`,`email`,`phone`,`region`,`home_group`,`clean_time`,`service_areas`,`availability`,`skills`,`notes`,`status`,`consent_at`) VALUES
('SVC-DEMO-0001','Pieter van Wyk','demo.volunteer@sarcna.org.za','+27 82 000 0002','Western Cape','Freedom Group','6 years','Registration, Hospitality, Transport','Friday afternoon and all day Saturday','Driver''s licence (code 10), first aid level 1','Happy to do the airport runs.','new',NOW()),
('SVC-DEMO-0002','Naledi Dlamini','demo.service@sarcna.org.za','+27 82 000 0003','Gauteng','Hope Group','2 years','Merchandise, Tea/Coffee','All weekend','Retail experience','First convention doing service.','reviewing',NOW());

INSERT INTO `contact_messages` (`name`,`email`,`phone`,`subject`,`message`,`status`) VALUES
('Sarah Adams','sarah.demo@example.com','+27 83 000 0004','Accommodation question','Hi there, can I book one bed in a shared cottage and have my friend book the other one? We would like to be in the same room.','new'),
('Johan Botha','johan.demo@example.com',NULL,'Transport from the airport','What time is the last shuttle from the airport on Friday? My flight lands at 15:20.','read');

-- ------------------------------------------------------------ finance demo --
-- Indicative budget and a few committed costs, so the finance screens are not
-- empty for the committee preview. All figures are MOCK.

INSERT INTO `budget_lines` (`kind`,`category`,`description`,`budgeted_cents`,`sort_order`,`notes`) VALUES
('income','Registration','Full weekend and day passes',38000000,1,'Assumes roughly 400 registrations at the blended rate'),
('income','Accommodation','Beds sold at the Retreat and the partner guest house',26000000,2,'72 beds on the estate over three nights plus overflow'),
('income','Transport','Shuttle seats',4500000,3,NULL),
('income','Merchandise','Clothing and keepsakes',6000000,4,NULL),
('income','Donations','Seventh Tradition and sponsorships',5000000,5,'Historically the least predictable line'),

('expense','Venue & accommodation','Retreat hire and cottage allocation',30000000,1,'Deposit due on signature'),
('expense','Catering','Meals across the weekend',12000000,2,NULL),
('expense','Transport & shuttles','Vehicle hire and drivers',3800000,3,NULL),
('expense','Merchandise stock','Garments, printing and packaging',3600000,4,NULL),
('expense','Programme & speakers','Travel and accommodation for speakers',2500000,5,NULL),
('expense','Equipment & AV','Sound, staging and screening room hire',1800000,6,NULL),
('expense','Printing & signage','Badges, lanyards, programmes and signage',900000,7,NULL),
('expense','Website & software','Hosting, domain and payment gateway',450000,8,NULL),
('expense','Banking & payment fees','PayFast and bank charges',1400000,9,'Roughly 3.5% of card and EFT income'),
('expense','Scholarships & sponsorships','Sponsored registrations and beds',3000000,10,'Funded from donations'),
('expense','Admin & sundries','Stationery, first aid, contingency',1200000,11,NULL);

INSERT INTO `expenses` (`reference`,`category_id`,`supplier`,`description`,`amount_cents`,`incurred_on`,`due_on`,`paid_on`,`status`,`payment_method`,`invoice_number`,`notes`) VALUES
('EXP-DEMO-0001',(SELECT id FROM expense_categories WHERE slug='venue-accommodation'),'Boschendal Retreat','Venue deposit — 25% on signature',7500000,'2026-08-01','2026-08-15','2026-08-14','paid','EFT','BOS-2027-001','Refundable until 45 days before arrival'),
('EXP-DEMO-0002',(SELECT id FROM expense_categories WHERE slug='website-software'),'Domain & hosting','Annual hosting and domain renewal',420000,'2026-08-10','2026-08-31','2026-08-12','paid','Card','HOST-88213',NULL),
('EXP-DEMO-0003',(SELECT id FROM expense_categories WHERE slug='printing-signage'),'Cape Print Co','Convention badges and lanyards — quote',780000,'2026-08-20','2027-07-01',NULL,'committed',NULL,NULL,'Quote valid to March 2027'),
('EXP-DEMO-0004',(SELECT id FROM expense_categories WHERE slug='equipment-av'),'Winelands AV','Sound and staging for the auditorium',1650000,'2026-08-22','2027-08-01',NULL,'committed',NULL,NULL,'50% deposit due July 2027'),
('EXP-DEMO-0005',(SELECT id FROM expense_categories WHERE slug='merchandise-stock' ),NULL,'Merchandise first run',0,'2026-08-25',NULL,NULL,'planned',NULL,NULL,'Awaiting final designs');

UPDATE `expenses` SET `category_id` = (SELECT id FROM expense_categories WHERE slug='merchandise'), `amount_cents` = 2400000 WHERE `reference` = 'EXP-DEMO-0005';
