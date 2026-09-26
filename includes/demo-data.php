<?php
/**
 * Demo content definition.
 *
 * Terms and posts are designed so that filter combinations are meaningful:
 * most posts belong to two categories and two or three tags, so selecting
 * e.g. "Travel" + "Food & Drink" (OR) and then "Budget" (AND) narrows the
 * grid step by step instead of jumping straight to zero results.
 *
 * @package TFPG
 */

defined( 'ABSPATH' ) || exit;

return array(
	'categories' => array(
		'travel'     => __( 'Travel', 'tfpg-posts-grid-filter' ),
		'food-drink' => __( 'Food & Drink', 'tfpg-posts-grid-filter' ),
		'technology' => __( 'Technology', 'tfpg-posts-grid-filter' ),
		'design'     => __( 'Design', 'tfpg-posts-grid-filter' ),
		'wellness'   => __( 'Wellness', 'tfpg-posts-grid-filter' ),
	),

	'tags'       => array(
		'beginner'    => __( 'Beginner', 'tfpg-posts-grid-filter' ),
		'advanced'    => __( 'Advanced', 'tfpg-posts-grid-filter' ),
		'how-to'      => __( 'How-to', 'tfpg-posts-grid-filter' ),
		'opinion'     => __( 'Opinion', 'tfpg-posts-grid-filter' ),
		'quick-read'  => __( 'Quick read', 'tfpg-posts-grid-filter' ),
		'in-depth'    => __( 'In-depth', 'tfpg-posts-grid-filter' ),
		'budget'      => __( 'Budget', 'tfpg-posts-grid-filter' ),
		'remote-work' => __( 'Remote work', 'tfpg-posts-grid-filter' ),
	),

	'posts'      => array(
		array(
			'key'        => 'lisbon-weekend',
			'title'      => __( 'A Weekend in Lisbon on a Shoestring', 'tfpg-posts-grid-filter' ),
			'excerpt'    => __( 'Trams, tiled facades and custard tarts: how to see the best of Lisbon in 48 hours without blowing your budget.', 'tfpg-posts-grid-filter' ),
			'content'    => array(
				__( 'Lisbon rewards slow wandering. Start in Alfama before the crowds arrive, when the only sounds are shutters opening and the clink of espresso cups.', 'tfpg-posts-grid-filter' ),
				__( 'A 24-hour transit pass covers the famous tram 28, the funiculars and the ferry across the Tagus, which doubles as the cheapest sunset cruise in town.', 'tfpg-posts-grid-filter' ),
				__( 'Eat where the locals eat: a prato do dia (dish of the day) at a neighbourhood tasca rarely costs more than ten euros, wine included.', 'tfpg-posts-grid-filter' ),
			),
			'categories' => array( 'travel', 'food-drink' ),
			'tags'       => array( 'budget', 'quick-read' ),
			'image_alt'  => __( 'Abstract warm-toned illustration for the Lisbon weekend guide', 'tfpg-posts-grid-filter' ),
		),
		array(
			'key'        => 'dark-mode-contrast',
			'title'      => __( 'Designing for Dark Mode Without Losing Contrast', 'tfpg-posts-grid-filter' ),
			'excerpt'    => __( 'Inverting colours is not a dark theme. A practical walkthrough of elevation, desaturation and contrast ratios that hold up at night.', 'tfpg-posts-grid-filter' ),
			'content'    => array(
				__( 'Pure black backgrounds with pure white text cause halation for many readers. Start from a very dark grey and reserve true black for large, calm surfaces.', 'tfpg-posts-grid-filter' ),
				__( 'In dark UIs, elevation is communicated with lighter surfaces rather than shadows. Define three or four surface tones and stick to them.', 'tfpg-posts-grid-filter' ),
				__( 'Saturated brand colours vibrate on dark backgrounds. Desaturate them slightly and re-check every pairing against WCAG contrast ratios.', 'tfpg-posts-grid-filter' ),
			),
			'categories' => array( 'design', 'technology' ),
			'tags'       => array( 'how-to', 'advanced' ),
			'image_alt'  => __( 'Abstract dark illustration for the dark mode design article', 'tfpg-posts-grid-filter' ),
		),
		array(
			'key'        => 'morning-stretch',
			'title'      => __( 'The Five-Minute Morning Stretch Routine', 'tfpg-posts-grid-filter' ),
			'excerpt'    => __( 'Five gentle moves you can do next to your bed to wake up your back, hips and shoulders before the day starts.', 'tfpg-posts-grid-filter' ),
			'content'    => array(
				__( 'You do not need a mat or special clothes. Begin with slow neck rolls, then reach both arms overhead and lean gently to each side.', 'tfpg-posts-grid-filter' ),
				__( 'Follow with a standing forward fold, knees soft, letting your head hang heavy. Roll up one vertebra at a time.', 'tfpg-posts-grid-filter' ),
				__( 'Finish with a hip-opening lunge on each side. Consistency beats intensity: five minutes every day does more than an hour once a week.', 'tfpg-posts-grid-filter' ),
			),
			'categories' => array( 'wellness' ),
			'tags'       => array( 'beginner', 'quick-read', 'how-to' ),
			'image_alt'  => __( 'Abstract calm illustration for the morning stretch routine', 'tfpg-posts-grid-filter' ),
		),
		array(
			'key'        => 'written-culture',
			'title'      => __( 'Why Every Remote Team Needs a Written Culture', 'tfpg-posts-grid-filter' ),
			'excerpt'    => __( 'Meetings do not scale across time zones; documents do. The case for writing things down, and how to make it stick.', 'tfpg-posts-grid-filter' ),
			'content'    => array(
				__( 'When a team spans eight time zones, the meeting that "only takes thirty minutes" costs someone their evening. Written proposals let everyone contribute on their own schedule.', 'tfpg-posts-grid-filter' ),
				__( 'Writing forces clarity. A decision that cannot be explained in a page is usually not ready to be made.', 'tfpg-posts-grid-filter' ),
				__( 'Start small: a shared template for decisions, a single place to find them, and the habit of linking to a document instead of re-explaining it in chat.', 'tfpg-posts-grid-filter' ),
			),
			'categories' => array( 'technology' ),
			'tags'       => array( 'opinion', 'remote-work', 'in-depth' ),
			'image_alt'  => __( 'Abstract illustration for the remote team written culture essay', 'tfpg-posts-grid-filter' ),
		),
		array(
			'key'        => 'bangkok-street-food',
			'title'      => __( 'Street Food Guide: Eating Well in Bangkok', 'tfpg-posts-grid-filter' ),
			'excerpt'    => __( 'From boat noodles to mango sticky rice: where to go, what to order and how to eat like a local for a few dollars a day.', 'tfpg-posts-grid-filter' ),
			'content'    => array(
				__( 'Follow the queues. A stall with a line of office workers at lunchtime is the most reliable review system in the city.', 'tfpg-posts-grid-filter' ),
				__( 'Each neighbourhood has its speciality: Yaowarat for late-night seafood, Victory Monument for boat noodles, and almost every corner for grilled pork skewers.', 'tfpg-posts-grid-filter' ),
				__( 'Carry small notes, point confidently, and do not skip dessert. Mango sticky rice is seasonal, so ask what is fresh.', 'tfpg-posts-grid-filter' ),
			),
			'categories' => array( 'travel', 'food-drink' ),
			'tags'       => array( 'budget', 'in-depth', 'how-to' ),
			'image_alt'  => __( 'Abstract vibrant illustration for the Bangkok street food guide', 'tfpg-posts-grid-filter' ),
		),
		array(
			'key'        => 'ergonomic-home-office',
			'title'      => __( 'Building a Home Office That Supports Your Back', 'tfpg-posts-grid-filter' ),
			'excerpt'    => __( 'Screen height, chair depth and the lighting nobody thinks about: small, affordable changes that make long workdays kinder to your body.', 'tfpg-posts-grid-filter' ),
			'content'    => array(
				__( 'Raise your screen until the top edge sits at eye level. A stack of books works just as well as an expensive monitor arm.', 'tfpg-posts-grid-filter' ),
				__( 'Your feet should rest flat and your knees sit slightly below your hips. If the chair is too high, a footrest is cheaper than a new chair.', 'tfpg-posts-grid-filter' ),
				__( 'Place the desk perpendicular to the window to avoid glare, and add a warm lamp for the evening so your eyes are not fighting the screen.', 'tfpg-posts-grid-filter' ),
			),
			'categories' => array( 'wellness', 'design' ),
			'tags'       => array( 'remote-work', 'how-to', 'budget' ),
			'image_alt'  => __( 'Abstract illustration for the ergonomic home office article', 'tfpg-posts-grid-filter' ),
		),
		array(
			'key'        => 'sourdough-basics',
			'title'      => __( 'Sourdough for Absolute Beginners', 'tfpg-posts-grid-filter' ),
			'excerpt'    => __( 'Flour, water, salt and patience. A no-jargon guide to your first starter and your first loaf.', 'tfpg-posts-grid-filter' ),
			'content'    => array(
				__( 'A starter is just flour and water that has been fed daily until wild yeast takes over. Expect it to take about a week to become reliably bubbly.', 'tfpg-posts-grid-filter' ),
				__( 'Your first loaf will probably be dense. That is normal. Watch the dough, not the clock: it is ready when it has grown by about half and jiggles when shaken.', 'tfpg-posts-grid-filter' ),
				__( 'Bake in a preheated lidded pot to trap steam. Remove the lid for the last twenty minutes to get a deep, crackling crust.', 'tfpg-posts-grid-filter' ),
			),
			'categories' => array( 'food-drink' ),
			'tags'       => array( 'beginner', 'how-to', 'in-depth' ),
			'image_alt'  => __( 'Abstract illustration for the sourdough beginners guide', 'tfpg-posts-grid-filter' ),
		),
		array(
			'key'        => 'typography-rules',
			'title'      => __( 'Typography Rules I Break on Purpose', 'tfpg-posts-grid-filter' ),
			'excerpt'    => __( 'Rules exist for good reasons, and knowing those reasons is what lets you break them well. Five I ignore regularly.', 'tfpg-posts-grid-filter' ),
			'content'    => array(
				__( '"Never use more than two typefaces." Sometimes a third, used for a single purpose such as numbers in a data table, makes the whole page clearer.', 'tfpg-posts-grid-filter' ),
				__( '"Body text must be at least 16px." On dense dashboards, a well-hinted 14px with generous line height reads better than cramped 16px.', 'tfpg-posts-grid-filter' ),
				__( 'The only rule I never break: test with real content. Lorem ipsum hides every problem that matters.', 'tfpg-posts-grid-filter' ),
			),
			'categories' => array( 'design' ),
			'tags'       => array( 'opinion', 'advanced' ),
			'image_alt'  => __( 'Abstract typographic illustration for the typography rules essay', 'tfpg-posts-grid-filter' ),
		),
		array(
			'key'        => 'one-bag-travel',
			'title'      => __( 'Packing Light: One Bag for Two Weeks', 'tfpg-posts-grid-filter' ),
			'excerpt'    => __( 'Skip the checked luggage queue forever. A tested packing list and the three rules that make carry-on only travel easy.', 'tfpg-posts-grid-filter' ),
			'content'    => array(
				__( 'Rule one: pack for one week, not two. Laundry exists everywhere, and a sink with a bit of soap works in a pinch.', 'tfpg-posts-grid-filter' ),
				__( 'Rule two: every item must go with every other item. Pick two base colours and one accent, and outfits multiply.', 'tfpg-posts-grid-filter' ),
				__( 'Rule three: wear your bulkiest items on travel days. Your heaviest shoes and jacket take up zero space in the bag.', 'tfpg-posts-grid-filter' ),
			),
			'categories' => array( 'travel' ),
			'tags'       => array( 'how-to', 'beginner', 'budget' ),
			'image_alt'  => __( 'Abstract illustration for the one bag travel packing guide', 'tfpg-posts-grid-filter' ),
		),
		array(
			'key'        => 'headless-wordpress',
			'title'      => __( 'Getting Started with Headless WordPress', 'tfpg-posts-grid-filter' ),
			'excerpt'    => __( 'When decoupling the front end pays off, when it does not, and the minimum setup to try it with the REST API.', 'tfpg-posts-grid-filter' ),
			'content'    => array(
				__( 'Headless WordPress keeps the editing experience and moves rendering to a separate front end. You gain freedom and lose everything the theme layer gave you for free.', 'tfpg-posts-grid-filter' ),
				__( 'Previews, SEO metadata, redirects and forms all need to be rebuilt or bridged. Budget for them before you commit.', 'tfpg-posts-grid-filter' ),
				__( 'To experiment, expose a custom post type with show_in_rest, fetch it from /wp-json/wp/v2/, and render it in the framework of your choice.', 'tfpg-posts-grid-filter' ),
			),
			'categories' => array( 'technology' ),
			'tags'       => array( 'advanced', 'in-depth', 'how-to' ),
			'image_alt'  => __( 'Abstract technical illustration for the headless WordPress article', 'tfpg-posts-grid-filter' ),
		),
		array(
			'key'        => 'digital-nomad-visas',
			'title'      => __( 'Digital Nomad Visas Compared', 'tfpg-posts-grid-filter' ),
			'excerpt'    => __( 'Income thresholds, tax rules and processing times for the most popular remote-work visas, side by side.', 'tfpg-posts-grid-filter' ),
			'content'    => array(
				__( 'Dozens of countries now offer visas aimed at remote workers, but the fine print varies wildly: some require proof of a monthly income, others a lump sum in savings.', 'tfpg-posts-grid-filter' ),
				__( 'Tax residency is the part most people overlook. Staying beyond a certain number of days can make you liable for local income tax.', 'tfpg-posts-grid-filter' ),
				__( 'Always check the official government source before applying. Rules change often, and third-party summaries, including this one, go out of date.', 'tfpg-posts-grid-filter' ),
			),
			'categories' => array( 'travel', 'technology' ),
			'tags'       => array( 'remote-work', 'in-depth' ),
			'image_alt'  => __( 'Abstract illustration for the digital nomad visas comparison', 'tfpg-posts-grid-filter' ),
		),
		array(
			'key'        => 'mindful-eating',
			'title'      => __( 'Mindful Eating in a Busy Week', 'tfpg-posts-grid-filter' ),
			'excerpt'    => __( 'You do not need a meditation retreat to eat with attention. Three small habits for meals that actually refuel you.', 'tfpg-posts-grid-filter' ),
			'content'    => array(
				__( 'Step away from the screen for the first five minutes of a meal. You will notice flavours, and fullness, you usually miss.', 'tfpg-posts-grid-filter' ),
				__( 'Cook one extra portion at dinner and pack it for lunch. Deciding in advance removes the 1pm scramble for whatever is closest.', 'tfpg-posts-grid-filter' ),
				__( 'Mindful does not mean perfect. The goal is to notice what and how you eat, not to judge it.', 'tfpg-posts-grid-filter' ),
			),
			'categories' => array( 'wellness', 'food-drink' ),
			'tags'       => array( 'beginner', 'quick-read', 'opinion' ),
			'image_alt'  => __( 'Abstract soft illustration for the mindful eating article', 'tfpg-posts-grid-filter' ),
		),
	),
);
