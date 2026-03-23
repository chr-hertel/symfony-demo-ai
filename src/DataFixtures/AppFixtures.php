<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DataFixtures;

use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\Tag;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\AbstractUnicodeString;
use Symfony\Component\String\Slugger\SluggerInterface;

use function Symfony\Component\String\u;

final class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly SluggerInterface $slugger,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $this->loadUsers($manager);
        $this->loadTags($manager);
        $this->loadPosts($manager);
    }

    private function loadUsers(ObjectManager $manager): void
    {
        foreach ($this->getUserData() as [$fullname, $username, $password, $email, $roles]) {
            $user = new User();
            $user->setFullName($fullname);
            $user->setUsername($username);
            $user->setPassword($this->passwordHasher->hashPassword($user, $password));
            $user->setEmail($email);
            $user->setRoles($roles);

            $manager->persist($user);

            $this->addReference($username, $user);
        }

        $manager->flush();
    }

    private function loadTags(ObjectManager $manager): void
    {
        foreach ($this->getTagData() as $name) {
            $tag = new Tag($name);

            $manager->persist($tag);

            $this->addReference('tag-'.$name, $tag);
        }

        $manager->flush();
    }

    private function loadPosts(ObjectManager $manager): void
    {
        foreach ($this->getPostData() as [$title, $slug, $summary, $content, $publishedAt, $author, $tags]) {
            $post = new Post();
            $post->setTitle($title);
            $post->setSlug($slug);
            $post->setSummary($summary);
            $post->setContent($content);
            $post->setPublishedAt($publishedAt);
            $post->setAuthor($author);
            $post->addTag(...$tags);

            foreach (range(1, 5) as $i) {
                $comment = new Comment();
                $comment->setAuthor($this->getReference('john_user', User::class));
                $comment->setContent($this->getRandomText(random_int(255, 512)));
                $comment->setPublishedAt(new \DateTimeImmutable('now + '.$i.'seconds'));

                $post->addComment($comment);
            }

            $manager->persist($post);
        }

        $manager->flush();
    }

    /**
     * @return array<array{string, string, string, string, array<string>}>
     */
    private function getUserData(): array
    {
        return [
            // $userData = [$fullname, $username, $password, $email, $roles];
            ['Jane Doe', 'jane_admin', 'kitten', 'jane_admin@symfony.com', [User::ROLE_ADMIN]],
            ['Tom Doe', 'tom_admin', 'kitten', 'tom_admin@symfony.com', [User::ROLE_ADMIN]],
            ['John Doe', 'john_user', 'kitten', 'john_user@symfony.com', [User::ROLE_USER]],
        ];
    }

    /**
     * @return string[]
     */
    private function getTagData(): array
    {
        return [
            'beaches',
            'mountains',
            'city-breaks',
            'food-and-wine',
            'history',
            'winter',
            'road-trips',
            'budget',
            'islands',
        ];
    }

    /**
     * @return array<int, array{0: string, 1: AbstractUnicodeString, 2: string, 3: string, 4: \DateTimeImmutable, 5: User, 6: array<Tag>}>
     *
     * @throws \Exception
     */
    private function getPostData(): array
    {
        $posts = [];
        $contents = $this->getPostContents();

        foreach ($this->getPhrases() as $i => $title) {
            // $postData = [$title, $slug, $summary, $content, $publishedAt, $author, $tags, $comments];
            $posts[] = [
                $title,
                $this->slugger->slug($title)->lower(),
                $this->getRandomText(),
                $contents[$i],
                new \DateTimeImmutable('now - '.$i.'days')->setTime(random_int(8, 17), random_int(7, 49), random_int(0, 59)),
                // Ensure that the first post is written by Jane Doe to simplify tests
                $this->getReference(['jane_admin', 'tom_admin'][0 === $i ? 0 : random_int(0, 1)], User::class),
                $this->getRandomTags(),
            ];
        }

        return $posts;
    }

    /**
     * @return string[]
     */
    private function getPhrases(): array
    {
        return [
            'Wandering the canals of Amsterdam in early spring',
            'A winter week in the Swiss Alps near Zermatt',
            'Island hopping through the Greek Cyclades',
            'Exploring the Amalfi Coast by scooter',
            'Christmas markets and mulled wine in Vienna',
            'Three days in Barcelona beyond the tourist trail',
            'The wild Atlantic coast of Portugal in autumn',
            'Hiking the Lofoten Islands under the midnight sun',
            'A food lovers guide to Lyon and Beaujolais',
            'Road tripping through the Scottish Highlands',
            'Summer sailing around the Croatian islands',
            'Discovering the fairy tale villages of Alsace',
            'Northern lights and hot springs in Iceland',
            'Cycling the Danube from Passau to Budapest',
            'Hidden beaches of Sardinia you need to visit',
            'A long weekend in Prague with a local',
            'Springtime in Provence lavender fields and beyond',
            'The best ski resorts in the Austrian Tyrol',
            'Walking the Camino de Santiago in September',
            'Dubrovnik to Montenegro a coastal road trip',
            'Truffle hunting in the hills of Istria',
            'The midnight sun in Finnish Lapland',
            'Exploring ancient ruins in Crete',
            'A rainy weekend in Edinburgh done right',
            'Lake Bled and the Julian Alps of Slovenia',
            'Sunset chasing along the Cinque Terre',
            'Berlin in November street art and cozy bars',
            'Ferry hopping the Dalmatian coast on a budget',
            'Autumn colours in the Bavarian countryside',
            'Porto and the Douro Valley a wine lovers escape',
        ];
    }

    private function getRandomText(int $maxLength = 255): string
    {
        $phrases = $this->getPhrases();
        shuffle($phrases);

        do {
            $text = u('. ')->join($phrases)->append('.');
            array_pop($phrases);
        } while ($text->length() > $maxLength);

        return $text;
    }

    /**
     * @return string[]
     */
    private function getPostContents(): array
    {
        return [
            <<<'MARKDOWN'
            Amsterdam in early spring is a different city from the summer crowds. The tulips
            are just beginning to bloom in the **Vondelpark**, and you can wander the canal
            rings almost undisturbed.

              * Rent a bike and ride along the Prinsengracht at sunrise
              * Visit the *Rijksmuseum* before the tour groups arrive
              * Try fresh stroopwafels at the Albert Cuyp market

            The Jordaan neighbourhood is perfect for an afternoon of aimless wandering. Tiny
            galleries, vintage shops, and brown cafés line every street. Stop at a terrace
            for a coffee and watch the houseboats drift by.

            If you have an extra day, take the train to **Haarlem** — a miniature Amsterdam
            with fewer tourists and a stunning central square. The Frans Hals Museum is worth
            the trip alone, and the surrounding dunes offer bracing walks along the North Sea.
            MARKDOWN,

            <<<'MARKDOWN'
            Zermatt in winter is all about the Matterhorn — that unmistakable silhouette
            dominates every view. The village itself is car-free, so the only sounds are
            boots crunching on snow and the occasional electric taxi humming past.

              * Ski the Glacier Paradise for runs above 3,800 metres
              * Warm up with *fondue moitié-moitié* at a mountainside hut
              * Take the Gornergrat railway for panoramic views

            The skiing here connects to **Cervinia** on the Italian side, giving you access
            to over 360 kilometres of pistes. Even non-skiers will find plenty to do: winter
            hiking trails are well-maintained and the views are staggering.

            Evenings in Zermatt revolve around hearty meals and early nights. The
            Bahnhofstrasse is lined with restaurants serving raclette and rösti, and most
            hotels have spas where you can soak tired muscles while gazing at the mountain.
            MARKDOWN,

            <<<'MARKDOWN'
            The Cyclades are best explored slowly. Ferries connect the islands like a blue
            and white constellation, and each one has its own personality. **Naxos** is green
            and lush, **Milos** is volcanic and dramatic, and tiny **Folegandros** feels like
            stepping back in time.

              * Start in Naxos for the best beaches and local cheese
              * Catch the ferry to *Paros* for windsurfing and nightlife
              * End in Santorini for the caldera views — arrive by boat for the best first impression

            Avoid July and August if you can. June and September offer warm seas, cheaper
            accommodation, and enough tavernas still open to eat grilled octopus every night.

            The real magic happens away from the postcard views. Rent a scooter, find a
            deserted cove, and swim until the sun starts to set. Then follow the smell of
            grilling fish to the nearest harbour and let the owner choose your meal.
            MARKDOWN,

            <<<'MARKDOWN'
            The Amalfi Coast is spectacularly beautiful and spectacularly steep. A scooter
            is the best way to navigate the hairpin turns of the **SS163** — just don't look
            down on the tight corners.

              * Start in Sorrento and wind south towards Amalfi
              * Stop in *Positano* for limoncello and clifftop views
              * Visit the lesser-known village of Atrani, tucked behind Amalfi

            Every village clings to the rocks like it was placed there by a set designer.
            Lemon groves cascade down terraces, fishing boats bob in tiny harbours, and the
            Mediterranean shimmers below.

            The food here is extraordinary. Fresh pasta with clams, fried zucchini flowers,
            and **delizia al limone** — a lemon sponge cake that tastes like concentrated
            sunshine. Eat at family-run trattorias away from the main roads for the best
            meals and prices.
            MARKDOWN,

            <<<'MARKDOWN'
            Vienna's Christmas markets are among the best in Europe. The scent of cinnamon,
            roasted chestnuts, and **Glühwein** fills the cold December air as you wander
            between wooden stalls.

              * Visit the Rathausplatz market for the full fairy-tale experience
              * Try *Kartoffelpuffer* (potato pancakes) with apple sauce
              * Explore the smaller Spittelberg market for handmade crafts

            Beyond the markets, Vienna in winter is a city of grand coffeehouses and warm
            interiors. Spend an afternoon at **Café Central** with a Sachertorte and the
            newspaper, just as the Viennese have done for centuries.

            Don't miss the evening concerts. From the Musikverein to small church recitals,
            the city is alive with music. Bundle up, walk along the illuminated Ring, and let
            the city work its old-world magic on you.
            MARKDOWN,

            <<<'MARKDOWN'
            Barcelona beyond La Rambla is where the city really comes alive. Skip the
            tourist traps and head to the **Gràcia** neighbourhood, where locals gather in
            small plazas lined with independent bars and restaurants.

              * Explore the Mercat de l'Abaceria for local produce
              * Walk the winding streets of *El Born* for boutiques and galleries
              * Catch sunset from the Bunkers del Carmel — the best free view in Barcelona

            The food scene here goes far beyond paella. Try a vermouth at a century-old
            bodega, eat pan con tomate at a market counter, or splurge on a tasting menu at
            one of the city's many innovative restaurants.

            Architecture is everywhere you look — not just Gaudí. The **Gothic Quarter**
            hides medieval courtyards and Roman ruins, and the Eixample district is a grid of
            Modernista facades that reward anyone who looks up.
            MARKDOWN,

            <<<'MARKDOWN'
            Portugal's Atlantic coast in autumn is moody and magnificent. The summer crowds
            have gone, the surf picks up, and the light turns golden along the **Alentejo**
            coastline.

              * Walk the Rota Vicentina trail through wildflower-covered cliffs
              * Surf at *Ericeira* or Peniche when the autumn swells arrive
              * Eat percebes (goose barnacles) at a seaside restaurant in Sagres

            The Algarve gets all the attention, but the stretch between Lisbon and the
            southwest corner is where Portugal feels most untouched. Fishing villages sit on
            clifftops, storks nest on church towers, and the pace of life slows right down.

            Evenings are for sitting in a local tasca with a glass of regional wine and a
            plate of **carne de porco à alentejana**. The warmth of Portuguese hospitality
            is at its best when tourist season is over and you're the only foreigner at
            the table.
            MARKDOWN,

            <<<'MARKDOWN'
            The Lofoten Islands above the Arctic Circle feel like another planet. Jagged
            peaks rise straight from the sea, fishing villages perch on rocky shores, and in
            summer the **midnight sun** never sets.

              * Hike Reinebringen for the classic Lofoten panorama
              * Kayak through the fjords around *Reine* at midnight
              * Stay in a traditional rorbu (fisherman's cabin) right on the water

            The light here is extraordinary. Photographers come from around the world to
            capture the way the sun circles the horizon, painting the mountains in shades of
            pink and gold at two in the morning.

            Don't skip the seafood. The cod drying racks you see everywhere are part of a
            tradition stretching back a thousand years. Try **skrei** (seasonal cod) at a
            local restaurant, or pick up fresh prawns straight from the boat.
            MARKDOWN,

            <<<'MARKDOWN'
            Lyon is arguably the gastronomic capital of France, and the surrounding
            **Beaujolais** vineyards make it a perfect food-and-wine destination. Start in
            the city's famous bouchons — traditional restaurants serving rich Lyonnais
            cuisine.

              * Try quenelles de brochet (pike dumplings) in a creamy sauce
              * Explore the covered *Les Halles de Paul Bocuse* food market
              * Drive into the Beaujolais hills for tastings at family-run domaines

            The old town of Lyon, **Vieux Lyon**, is a UNESCO site with Renaissance
            traboules — hidden passageways that cut through buildings. Wander them on a
            quiet morning before the city wakes up.

            In Beaujolais, the villages of Fleurie and Morgon produce wines that challenge
            everything you thought you knew about the region. These are serious, age-worthy
            bottles served in centuries-old cellars by winemakers who've been at it for
            generations.
            MARKDOWN,

            <<<'MARKDOWN'
            The Scottish Highlands reward slow travel. Narrow single-track roads wind through
            glens, past lochs, and over mountain passes where sheep outnumber cars. The
            **North Coast 500** is the classic route, but going off-script is even better.

              * Drive the Applecross peninsula via the Bealach na Bà pass
              * Stop at *Eilean Donan Castle* at sunset for the iconic photo
              * Wild camp by a loch — Scotland's right to roam makes it legal

            The weather is part of the experience. Rain, sunshine, mist, and dramatic skies
            can cycle through in a single hour. Pack layers and embrace it — the landscapes
            are at their most dramatic when the clouds are low.

            End each day at a village pub with a dram of single malt and a plate of
            **cullen skink**. The Highlands are not about luxury — they're about space, silence,
            and scenery that stays with you long after you leave.
            MARKDOWN,

            <<<'MARKDOWN'
            Croatia's coastline was made for sailing. The Adriatic is calm, the islands are
            plentiful, and every harbour has a **konoba** waiting to serve you grilled fish
            and local wine.

              * Sail from Split to Hvar, Vis, and Korčula
              * Anchor in the *Blue Lagoon* near Drvenik for a swim
              * Eat peka (slow-cooked meat under a bell) on the island of Vis

            July and August are peak season, but late June or September offer perfect
            conditions with fewer boats in the marinas. The mistral wind keeps afternoons
            comfortable and makes for excellent sailing.

            Vis is a particular highlight. Closed to tourists until the 1990s, it has a
            wild, untouched quality. The fishing village of **Komiža** is one of the most
            atmospheric places on the Adriatic, and the military tunnels-turned-bars add a
            unique edge.
            MARKDOWN,

            <<<'MARKDOWN'
            Alsace feels like a storybook illustration come to life. Half-timbered houses in
            every colour line cobblestone streets, and the scent of **tarte flambée** drifts
            from open restaurant doors.

              * Walk the Route des Vins through Riquewihr, Eguisheim, and Kaysersberg
              * Try a glass of *Gewürztraminer* paired with Munster cheese
              * Visit Colmar's Petite Venise district by punt boat

            The region's blend of French and German influences creates a unique culture.
            Menus offer both choucroute garnie and coq au Riesling, and the local dialect is
            neither quite French nor quite German.

            Christmas in Alsace is particularly magical. **Strasbourg's Christkindelsmärik**,
            dating back to 1570, is one of the oldest Christmas markets in Europe. Every
            village adds its own decorations, and the entire region glows with festive light
            from late November through December.
            MARKDOWN,

            <<<'MARKDOWN'
            Iceland delivers on every dramatic landscape you've imagined — and then some. In
            winter, the **northern lights** dance overhead, and natural hot springs steam in
            the cold dark air.

              * Drive the Golden Circle for geysers, waterfalls, and tectonic plates
              * Soak in the *Secret Lagoon* in Flúðir — quieter than the Blue Lagoon
              * Chase the aurora from the Snæfellsnes peninsula on a clear night

            The country is expensive, but renting a campervan and cooking your own meals
            keeps costs manageable. Stock up at Bónus supermarkets and eat hot dogs from the
            famous stand in Reykjavík.

            Don't underestimate the winter weather. Roads can close without warning, and
            daylight is scarce in December. But the payoff is extraordinary: **ice caves**
            inside glaciers, frozen waterfalls, and the feeling of having the entire country
            almost to yourself.
            MARKDOWN,

            <<<'MARKDOWN'
            The EuroVelo 6 route along the Danube is one of the great European cycle paths.
            Starting in **Passau**, where three rivers meet, you follow the Danube east
            through Austria, Slovakia, and into Hungary.

              * Ride through the Wachau Valley past vineyards and medieval castles
              * Stop in *Bratislava* for a day of affordable food and lively bars
              * Arrive in Budapest and reward yourself with the thermal baths

            The route is almost entirely flat, well-signed, and dotted with guesthouses and
            beer gardens. You don't need to be a serious cyclist — families and retirees do
            this route all summer.

            The Wachau section between Melk and Krems is the scenic highlight. **Dürnstein**,
            where Richard the Lionheart was imprisoned, sits on a bend of the river
            surrounded by terraced vineyards. Stop for a glass of Grüner Veltliner and watch
            the barges drift past.
            MARKDOWN,

            <<<'MARKDOWN'
            Sardinia's coastline hides beaches that rival the Caribbean — without the
            long-haul flight. The **Costa Smeralda** gets the headlines, but the island's
            best sand is often free and deserted.

              * Visit Cala Goloritzé, accessible only by boat or a steep hike
              * Swim at *Spiaggia della Pelosa* near Stintino for turquoise shallows
              * Explore the coves of the Golfo di Orosei by rubber dinghy

            Away from the coast, Sardinia is surprisingly rugged and mountainous. The
            interior is a world of shepherds, cork forests, and ancient nuraghi — stone
            towers from a Bronze Age civilisation unique to the island.

            The food reflects this dual character: **bottarga** (cured mullet roe) and fresh
            seafood on the coast, roast suckling pig and pecorino cheese in the hills. Pair
            everything with a glass of Cannonau, the island's robust red wine.
            MARKDOWN,

            <<<'MARKDOWN'
            Prague is a city that rewards getting lost. Once you step away from the Old Town
            Square and Charles Bridge, the crowds thin and the real character of the city
            emerges. The **Vinohrady** and Žižkov neighbourhoods are full of local pubs,
            quirky cafés, and art nouveau architecture.

              * Drink unpasteurised tank beer at a neighbourhood hospoda
              * Walk through *Letná Park* for views over the river and rooftops
              * Visit the DOX Centre for Contemporary Art in Holešovice

            Czech beer culture is the best in the world — and the cheapest. A half-litre of
            excellent lager costs less than a coffee in most European capitals. Take your
            time, order a plate of utopenec (pickled sausage), and settle in.

            The architecture alone is worth the trip. From the astronomical clock to the
            **Dancing House**, Prague layers centuries of style in every street. Visit in
            spring or autumn when the light is soft and the tourist buses are fewer.
            MARKDOWN,

            <<<'MARKDOWN'
            Provence in spring is a feast for the senses. The lavender isn't in full bloom
            yet — that comes in June and July — but the markets overflow with artichokes,
            asparagus, and **rosé from nearby vineyards**.

              * Explore the hilltop village of Gordes at dawn before the day-trippers arrive
              * Shop the *Tuesday market in Vaison-la-Romaine* for olives and honey
              * Drive the ochre-coloured village of Roussillon and its surrounding trails

            The Luberon valley is the heart of Provençal life. Stone farmhouses sit among
            cherry orchards, and the air smells of thyme and pine. Every village has a
            fountain, a café, and a view.

            Plan your days around meals. A long lunch under a plane tree with **daube
            provençale** and a carafe of local red is not laziness — it's the whole point of
            being here. Dinner can wait until the cicadas start singing.
            MARKDOWN,

            <<<'MARKDOWN'
            The Austrian Tyrol offers some of the best skiing in Europe, with reliable snow,
            well-groomed pistes, and mountain huts that serve proper meals — not just
            overpriced sandwiches. **St. Anton** is the classic choice for serious skiers.

              * Ski the Arlberg region connecting St. Anton, Lech, and Zürs
              * Try *Kaiserschmarrn* (shredded pancake) at a summit restaurant
              * Relax in Innsbruck after skiing — the old town is charming and compact

            The smaller resorts are often better value. Sölden, the Stubai Valley, and
            Kitzbühel all deliver excellent skiing without the St. Anton price tag, and the
            après-ski is just as lively.

            Non-skiers can snowshoe, toboggan, or simply sit on a sunny terrace with a hot
            chocolate and **Apfelstrudel**. The Tyrolean mountains are beautiful whether
            you're racing down them or just looking up.
            MARKDOWN,

            <<<'MARKDOWN'
            The Camino de Santiago is more than a walk — it's a reset. In September, the
            summer heat has broken, the crowds have thinned, and the **Meseta** stretches
            ahead in golden light.

              * Start in Sarria for the last 115 kilometres to Santiago
              * Walk the full *Camino Francés* from Saint-Jean-Pied-de-Port for the complete experience
              * Carry less than you think — a 7 kg pack is more than enough

            The rhythm of the Camino is simple: walk, eat, sleep, repeat. You fall into it
            within days. Albergues (pilgrim hostels) are basic but welcoming, and the
            communal dinners are where friendships form.

            Arriving at the **Cathedral of Santiago de Compostela** is emotional regardless of
            your reasons for walking. Watch the Botafumeiro swing during the pilgrim mass,
            then celebrate with a plate of pulpo a feira and a glass of Albariño.
            MARKDOWN,

            <<<'MARKDOWN'
            The coast from Dubrovnik to Montenegro is one of the most scenic drives in
            Europe. The road hugs the **Adriatic**, weaving between medieval towns, rocky
            coves, and the dramatic Bay of Kotor.

              * Start in Dubrovnik and walk the city walls early in the morning
              * Cross into Montenegro and drive the *Bay of Kotor* — Europe's southernmost fjord
              * Explore the fortified town of Kotor and hike to the fortress above it

            The border crossing is quick, and Montenegro is significantly cheaper than
            Croatia. A seafood lunch in Perast or Kotor costs a fraction of Dubrovnik
            prices, with equally stunning views.

            The Bay of Kotor is the highlight. Surrounded by mountains that drop almost
            vertically into the water, the bay narrows to a dramatic strait. The tiny island
            churches of **Our Lady of the Rocks** and St. George sit in the middle like
            something from a painting.
            MARKDOWN,

            <<<'MARKDOWN'
            Istria's truffle season runs from autumn through winter, and the forests around
            **Motovun** and Buzet hide some of the finest white and black truffles in the
            world — rivalling those of Alba, Italy.

              * Join a truffle hunt with trained dogs through the Motovun forest
              * Eat truffle-shaved *fuži* pasta at a konoba in the hilltop villages
              * Visit the Zigante restaurant, famous for discovering a record-breaking white truffle

            Beyond truffles, Istria is Croatia's food heartland. Olive oils here win
            international awards, the wine — especially Malvazija — is excellent, and the
            hilltop towns feel more Tuscan than Balkan.

            The coastline around **Rovinj** adds a different flavour. This photogenic fishing
            port has a Venetian old town, clear swimming waters, and restaurants that serve
            the day's catch straight from the harbour.
            MARKDOWN,

            <<<'MARKDOWN'
            Finnish Lapland under the midnight sun is surreal. In June and July, the sun
            refuses to set, and the landscape is bathed in a continuous warm glow. The
            forests, lakes, and fells of **Inari** and Utsjoki feel truly remote.

              * Hike in Urho Kekkonen National Park through birch forests and fell plateaus
              * Paddle a canoe across *Lake Inari* in the endless daylight
              * Visit a Sámi reindeer farm and learn about indigenous culture

            The silence is the first thing you notice. Then the space. There are more
            reindeer than people in Lapland, and entire days can pass without seeing another
            soul on the trail.

            Midsummer in Finland is celebrated with bonfires, saunas, and swimming. Find a
            lakeside **mökki** (cabin), heat up the sauna, and plunge into the cold water
            under a sun that never quite disappears.
            MARKDOWN,

            <<<'MARKDOWN'
            Crete is the largest Greek island and could easily fill a month. The **Minoan
            ruins** at Knossos are just the beginning — the island's gorges, mountain
            villages, and southern coast beaches are the real treasures.

              * Hike the Samariá Gorge, one of Europe's longest, to the Libyan Sea
              * Explore *Chania's* Venetian harbour and the backstreet tavernas
              * Drive the wild south coast to Loutro, accessible only by boat or foot

            Cretan food is legendary. The island diet — based on olive oil, wild greens,
            cheese, and grilled meat — is one of the healthiest in the world. Every meal
            starts with a basket of bread and a carafe of raki, both on the house.

            The mountains divide the island into distinct regions. The **White Mountains** in
            the west are dramatic and sparsely populated, while the east around Sitia is
            gentler and less visited. Both deserve time.
            MARKDOWN,

            <<<'MARKDOWN'
            Edinburgh in the rain has a particular charm. The stone buildings darken, the
            closes and wynds become atmospheric tunnels, and the castle looms through the
            mist like it was designed for exactly this weather.

              * Walk the Royal Mile from the Castle to Holyrood Palace
              * Shelter in the *Scottish National Gallery* — it's free and world-class
              * Find a pub on the Grassmarket for a whisky flight and a pie

            Arthur's Seat is worth climbing even in the rain — the views over the city and
            the Firth of Forth are dramatic, and the wind at the top will blow the cobwebs
            away.

            Edinburgh's food scene has evolved enormously. Beyond haggis (which you should
            absolutely try), the city has excellent **seafood restaurants**, Indian street
            food stalls, and bakeries producing some of the best sourdough in Britain.
            MARKDOWN,

            <<<'MARKDOWN'
            Lake Bled is the image that launched a thousand Instagram posts — and it genuinely
            lives up to the hype. The emerald lake, the island church, and the clifftop
            **Bled Castle** form a composition that looks unreal from every angle.

              * Row a traditional pletna boat to the island and ring the wishing bell
              * Hike to the *Vintgar Gorge* for wooden walkways above turquoise rapids
              * Drive over the Vršič Pass into the Soča Valley for world-class kayaking

            Slovenia packs an extraordinary amount into a tiny country. In a single day you
            can swim in a lake, hike an alpine trail, and eat seafood on the coast.

            The Julian Alps behind Bled are the real prize. **Triglav National Park** offers
            hiking that rivals the better-known Alps without the crowds. Stay in a mountain
            hut, eat jota (bean and sauerkraut stew), and wake up to views that make you
            wonder why you've never been here before.
            MARKDOWN,

            <<<'MARKDOWN'
            The Cinque Terre at sunset is one of Italy's most magical sights. As the light
            turns golden, the five colourful villages glow against the dark Mediterranean,
            and the terraced vineyards above catch the last rays of the day.

              * Walk the Sentiero Azzurro trail connecting the five villages
              * Eat *focaccia di Recco* — thin, cheese-filled flatbread — in any village bakery
              * Take the boat between villages for the best views of the coastline

            The villages are car-free and connected by trail and train. **Manarola** and
            Vernazza are the most photogenic, but Corniglia, perched high on a cliff, has the
            best views and the fewest tourists.

            The local wine, Sciacchetrà, is a sweet dessert wine made from grapes dried on
            the terraces. It's rare, expensive, and worth every sip. Pair it with a plate of
            fresh anchovies and watch the fishing boats return to harbour.
            MARKDOWN,

            <<<'MARKDOWN'
            Berlin in November strips away the glossy tourism and reveals the city's true
            character. The **street art** is as vivid as ever, the bars are warm and
            inventive, and the cultural calendar is packed.

              * Explore the East Side Gallery and Kreuzberg's ever-changing murals
              * Drink natural wine at a *Neukölln* bar with no sign on the door
              * Visit the Hamburger Bahnhof for contemporary art in a former train station

            The city's nightlife needs no introduction, but November is also when Berlin's
            cosier side emerges. Cozy Kneipen (pubs) serve Flammkuchen and Glühwein, and
            the first Christmas markets begin to appear at the end of the month.

            History is everywhere. The **Topography of Terror** and the Memorial to the
            Murdered Jews of Europe are essential visits. Berlin doesn't hide from its past —
            it confronts it openly, and that honesty is part of what makes the city so
            compelling.
            MARKDOWN,

            <<<'MARKDOWN'
            The Dalmatian coast is perfectly suited to budget travel. Ferries are affordable,
            accommodation outside July and August is reasonable, and the food — especially
            if you eat where locals eat — is outstanding value.

              * Take the Jadrolinija ferry from Split to Brač or Hvar
              * Stay in *sobe* (private rooms) rather than hotels
              * Eat burek from a bakery for breakfast — flaky pastry filled with cheese or meat

            The coast is strung with walled towns that were once Venetian outposts. **Trogir**
            is a complete medieval city on a tiny island, and Šibenik's cathedral is a
            UNESCO-listed masterpiece — both are far less crowded than Dubrovnik.

            Wild swimming is everywhere. Pull over at any rocky stretch of coast, climb down,
            and you'll find crystal-clear water and flat rocks for sunbathing. Pack a mask
            and snorkel — the **underwater visibility** in the Adriatic is extraordinary.
            MARKDOWN,

            <<<'MARKDOWN'
            The Bavarian countryside in autumn is a canvas of amber, gold, and russet. The
            forests around **Berchtesgaden** and the Romantic Road blaze with colour, and the
            air carries the smell of woodsmoke and fallen leaves.

              * Drive the Romantic Road from Würzburg to Füssen, stopping at walled towns
              * Visit *Neuschwanstein Castle* — kitsch but unforgettable against autumn foliage
              * Hike around the Königssee, Germany's cleanest lake, surrounded by mountains

            October means Erntedankfest (harvest festival) and the tail end of Oktoberfest.
            Smaller beer festivals in market towns offer the same Bavarian atmosphere without
            the Munich mayhem.

            The food is designed for the season. **Schweinshaxe** (roasted pork knuckle),
            Käsespätzle (cheesy egg noodles), and Dampfnudeln (steamed dumplings) are
            hearty, warming, and best enjoyed in a wood-panelled Gasthof with a wheat beer
            in hand.
            MARKDOWN,

            <<<'MARKDOWN'
            Porto is a city of steep hills, tiled facades, and the winding **Douro River**
            cutting through its heart. Combined with the wine region upstream, it makes for
            one of Europe's most satisfying short trips.

              * Cross the Dom Luís I Bridge for the classic Porto panorama
              * Taste port wine in the *Vila Nova de Gaia* cellars across the river
              * Take the train up the Douro Valley through terraced vineyards to Pinhão

            The Ribeira waterfront is lively but touristy — climb uphill to the Bolhão
            market and the streets around Rua de Santa Catarina for a more local experience.
            A francesinha (the city's iconic meat sandwich drowning in cheese and sauce) is
            mandatory.

            The Douro Valley is one of the oldest demarcated wine regions in the world. The
            terraced hillsides produce not just port but excellent **still wines** — both red
            and white. Stay at a quinta (estate), taste straight from the barrel, and watch
            the river curve below.
            MARKDOWN,
        ];
    }

    /**
     * @return array<Tag>
     *
     * @throws \Exception
     */
    private function getRandomTags(): array
    {
        $tagNames = $this->getTagData();
        shuffle($tagNames);
        $selectedTags = \array_slice($tagNames, 0, random_int(2, 4));

        return array_map(
            fn ($tagName) => $this->getReference('tag-'.$tagName, Tag::class),
            $selectedTags
        );
    }
}
