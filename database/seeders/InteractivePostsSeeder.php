<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class InteractivePostsSeeder extends Seeder
{
    public function run(): void
    {
        $authorId = 1;
        $categories = Category::pluck('id', 'slug');

        // Quiz 1: African Geography
        $this->createPost([
            'title' => 'How Well Do You Know African Geography?',
            'slug' => 'african-geography-quiz',
            'excerpt' => 'Test your knowledge of the African continent with this fun geography quiz!',
            'content' => '<p>Africa is the second-largest continent in the world, home to 54 countries, diverse landscapes, and rich cultures. Think you know your way around? Take this quiz to find out!</p>',
            'post_type' => 'quiz',
            'type_data' => [
                'questions' => [
                    [
                        'question' => 'What is the largest country in Africa by land area?',
                        'answers' => [
                            ['text' => 'Democratic Republic of Congo', 'is_correct' => false],
                            ['text' => 'Algeria', 'is_correct' => true],
                            ['text' => 'Sudan', 'is_correct' => false],
                            ['text' => 'Nigeria', 'is_correct' => false],
                        ],
                        'explanation' => 'Algeria is the largest African country at 2.38 million km², making it the 10th largest in the world.',
                    ],
                    [
                        'question' => 'Which African lake is the largest by surface area?',
                        'answers' => [
                            ['text' => 'Lake Tanganyika', 'is_correct' => false],
                            ['text' => 'Lake Malawi', 'is_correct' => false],
                            ['text' => 'Lake Victoria', 'is_correct' => true],
                            ['text' => 'Lake Chad', 'is_correct' => false],
                        ],
                        'explanation' => 'Lake Victoria, shared by Tanzania, Uganda, and Kenya, is the largest lake in Africa at 68,800 km².',
                    ],
                    [
                        'question' => 'What is the highest mountain in Africa?',
                        'answers' => [
                            ['text' => 'Mount Kenya', 'is_correct' => false],
                            ['text' => 'Mount Kilimanjaro', 'is_correct' => true],
                            ['text' => 'Mount Elgon', 'is_correct' => false],
                            ['text' => 'Ras Dashen', 'is_correct' => false],
                        ],
                        'explanation' => 'Mount Kilimanjaro in Tanzania stands at 5,895 meters, making it the highest peak in Africa.',
                    ],
                    [
                        'question' => 'Which country is known as the "Rainbow Nation"?',
                        'answers' => [
                            ['text' => 'Kenya', 'is_correct' => false],
                            ['text' => 'Ghana', 'is_correct' => false],
                            ['text' => 'South Africa', 'is_correct' => true],
                            ['text' => 'Cameroon', 'is_correct' => false],
                        ],
                        'explanation' => 'South Africa was nicknamed the "Rainbow Nation" by Archbishop Desmond Tutu to describe the country\'s multicultural diversity.',
                    ],
                    [
                        'question' => 'The Sahara Desert spans how many African countries?',
                        'answers' => [
                            ['text' => '7', 'is_correct' => false],
                            ['text' => '11', 'is_correct' => true],
                            ['text' => '5', 'is_correct' => false],
                            ['text' => '9', 'is_correct' => false],
                        ],
                        'explanation' => 'The Sahara spans 11 countries: Algeria, Chad, Egypt, Libya, Mali, Mauritania, Morocco, Niger, Sudan, Tunisia, and Western Sahara.',
                    ],
                ],
                'passing_score' => 60,
                'show_answers' => true,
            ],
            'author_id' => $authorId,
            'category_slugs' => ['culture'],
        ]);

        // Quiz 2: African Music
        $this->createPost([
            'title' => 'Can You Name These African Music Genres?',
            'slug' => 'african-music-genres-quiz',
            'excerpt' => 'From Afrobeats to Amapiano — how well do you know African music?',
            'content' => '<p>African music has taken the world by storm. From Lagos to Johannesburg, the continent produces some of the most vibrant sounds on Earth. Let\'s see how much you know!</p>',
            'post_type' => 'quiz',
            'type_data' => [
                'questions' => [
                    [
                        'question' => 'Amapiano originated in which country?',
                        'answers' => [
                            ['text' => 'Nigeria', 'is_correct' => false],
                            ['text' => 'South Africa', 'is_correct' => true],
                            ['text' => 'Ghana', 'is_correct' => false],
                            ['text' => 'Tanzania', 'is_correct' => false],
                        ],
                        'explanation' => 'Amapiano emerged in South Africa around 2012, blending deep house, jazz, and lounge music.',
                    ],
                    [
                        'question' => 'Which artist is often called the "King of Afrobeats"?',
                        'answers' => [
                            ['text' => 'Burna Boy', 'is_correct' => false],
                            ['text' => 'Wizkid', 'is_correct' => false],
                            ['text' => 'Fela Kuti', 'is_correct' => true],
                            ['text' => 'Davido', 'is_correct' => false],
                        ],
                        'explanation' => 'Fela Kuti, the Nigerian musician and activist, is widely credited as the pioneer and king of Afrobeat (the original genre).',
                    ],
                    [
                        'question' => 'What is "Highlife" music primarily associated with?',
                        'answers' => [
                            ['text' => 'East Africa', 'is_correct' => false],
                            ['text' => 'West Africa', 'is_correct' => true],
                            ['text' => 'Southern Africa', 'is_correct' => false],
                            ['text' => 'North Africa', 'is_correct' => false],
                        ],
                        'explanation' => 'Highlife is a music genre that originated in Ghana and spread across West Africa, blending traditional melodies with Western instruments.',
                    ],
                    [
                        'question' => 'Bongo Flava is a popular music genre from which country?',
                        'answers' => [
                            ['text' => 'Kenya', 'is_correct' => false],
                            ['text' => 'Uganda', 'is_correct' => false],
                            ['text' => 'Tanzania', 'is_correct' => true],
                            ['text' => 'Rwanda', 'is_correct' => false],
                        ],
                        'explanation' => 'Bongo Flava originated in Tanzania in the 1990s, blending hip hop with traditional Tanzanian sounds.',
                    ],
                ],
                'passing_score' => 50,
                'show_answers' => true,
            ],
            'author_id' => $authorId,
            'category_slugs' => ['entertainment'],
        ]);

        // Trivia 1: Africa Facts
        $this->createPost([
            'title' => '10 Mind-Blowing Facts About Africa',
            'slug' => 'mind-blowing-africa-facts',
            'excerpt' => 'Swipe through these incredible facts about the African continent that will surprise you.',
            'content' => '<p>Africa is a continent full of surprises. From ancient civilizations to modern marvels, here are some facts that might just blow your mind.</p>',
            'post_type' => 'trivia',
            'type_data' => [
                'facts' => [
                    ['text' => 'Africa is home to the world\'s oldest university — the University of al-Qarawiyyin in Fez, Morocco, founded in 859 AD.', 'source' => 'UNESCO World Heritage'],
                    ['text' => 'The African continent is large enough to fit the US, China, India, Japan, and most of Europe inside it combined.', 'source' => 'National Geographic'],
                    ['text' => 'There are over 2,000 recognized languages spoken in Africa, making it the most linguistically diverse continent.', 'source' => 'Ethnologue'],
                    ['text' => 'Lake Malawi has more species of fish than any other lake in the world — over 1,000 species of cichlids alone.', 'source' => 'World Wildlife Fund'],
                    ['text' => 'The Sahara Desert is roughly the same size as the United States of America.', 'source' => 'NASA Earth Observatory'],
                    ['text' => 'Africa has the youngest population of any continent — 60% of the population is under 25 years old.', 'source' => 'African Development Bank'],
                    ['text' => 'The Great Rift Valley stretches over 6,000 km from Lebanon to Mozambique and is visible from space.', 'source' => 'Smithsonian Institution'],
                    ['text' => 'Nigeria\'s film industry, Nollywood, is the second largest in the world by number of films produced, surpassing Hollywood.', 'source' => 'UNESCO Institute for Statistics'],
                    ['text' => 'Africa is the only continent that stretches across all four hemispheres — north, south, east, and west.', 'source' => 'World Atlas'],
                    ['text' => 'The ancient Egyptians invented paper, pens, locks, keys, and toothpaste.', 'source' => 'British Museum'],
                ],
                'source_url' => '',
            ],
            'author_id' => $authorId,
            'category_slugs' => ['culture'],
        ]);

        // Trivia 2: African Tech
        $this->createPost([
            'title' => 'Amazing Facts About Africa\'s Tech Boom',
            'slug' => 'african-tech-boom-facts',
            'excerpt' => 'Africa\'s tech scene is exploding. Here are some facts that prove it.',
            'content' => '<p>From mobile money to AI startups, Africa is quietly becoming a global tech powerhouse. Swipe through these facts to see why investors and innovators are paying attention.</p>',
            'post_type' => 'trivia',
            'type_data' => [
                'facts' => [
                    ['text' => 'M-Pesa, launched in Kenya in 2007, processes more transactions annually than PayPal and has over 50 million active users.', 'source' => 'Safaricom Annual Report'],
                    ['text' => 'African startups raised over $5 billion in venture capital funding in 2022 alone.', 'source' => 'Partech Africa'],
                    ['text' => 'Nigeria has the largest number of tech hubs in Africa, with over 90 active innovation centers.', 'source' => 'GSMA'],
                    ['text' => 'Rwanda uses drones to deliver blood and medical supplies to remote hospitals, serving over 12 million people.', 'source' => 'Zipline'],
                    ['text' => 'Kenya\'s Silicon Savannah is home to over 200 tech startups and was one of the first tech ecosystems in Africa.', 'source' => 'iHub Nairobi'],
                    ['text' => 'South Africa\'s MeerKAT telescope, part of the Square Kilometre Array, is one of the most powerful radio telescopes ever built.', 'source' => 'SARAO'],
                ],
                'source_url' => '',
            ],
            'author_id' => $authorId,
            'category_slugs' => ['technology'],
        ]);

        // Poll 1: AFCON
        $this->createPost([
            'title' => 'Who Will Win AFCON 2027?',
            'slug' => 'who-will-win-afcon-2027',
            'excerpt' => 'Cast your vote for the next Africa Cup of Nations champion!',
            'content' => '<p>The Africa Cup of Nations is the most prestigious football tournament on the continent. With powerhouse teams preparing for 2027, who do you think will lift the trophy?</p>',
            'post_type' => 'poll',
            'type_data' => [
                'options' => [
                    ['text' => 'Nigeria (Super Eagles)'],
                    ['text' => 'Ivory Coast (Elephants)'],
                    ['text' => 'Morocco (Atlas Lions)'],
                    ['text' => 'Senegal (Lions of Teranga)'],
                    ['text' => 'Egypt (Pharaohs)'],
                    ['text' => 'Cameroon (Indomitable Lions)'],
                    ['text' => 'Ghana (Black Stars)'],
                ],
                'allow_multiple' => false,
                'show_results_before_vote' => false,
                'ends_at' => null,
            ],
            'author_id' => $authorId,
            'category_slugs' => ['sports'],
        ]);

        // Poll 2: Best African City
        $this->createPost([
            'title' => 'What\'s the Best City to Live in Africa?',
            'slug' => 'best-city-to-live-in-africa',
            'excerpt' => 'From Lagos to Cape Town — which African city would you call home?',
            'content' => '<p>Africa\'s cities are booming with culture, opportunity, and energy. If you could live anywhere on the continent, where would it be? Vote for your top pick!</p>',
            'post_type' => 'poll',
            'type_data' => [
                'options' => [
                    ['text' => 'Lagos, Nigeria'],
                    ['text' => 'Nairobi, Kenya'],
                    ['text' => 'Cape Town, South Africa'],
                    ['text' => 'Accra, Ghana'],
                    ['text' => 'Kigali, Rwanda'],
                    ['text' => 'Cairo, Egypt'],
                    ['text' => 'Dar es Salaam, Tanzania'],
                    ['text' => 'Casablanca, Morocco'],
                ],
                'allow_multiple' => false,
                'show_results_before_vote' => true,
                'ends_at' => null,
            ],
            'author_id' => $authorId,
            'category_slugs' => ['culture'],
        ]);

        // Poll 3: African Music (with end date)
        $this->createPost([
            'title' => 'Best African Album of 2026 So Far?',
            'slug' => 'best-african-album-2026',
            'excerpt' => 'Which African album has been your favorite this year? Vote now!',
            'content' => '<p>2026 has already delivered incredible music from across the continent. From Afrobeats to Amapiano, which album has been on repeat for you?</p>',
            'post_type' => 'poll',
            'type_data' => [
                'options' => [
                    ['text' => 'Burna Boy — "African Giant II"'],
                    ['text' => 'Tems — "Born in Lagos"'],
                    ['text' => 'Wizkid — "Made in Lagos Deluxe"'],
                    ['text' => 'Asake — "Lungu Boy Forever"'],
                    ['text' => 'Ayra Starr — "The Year I Turned 25"'],
                    ['text' => 'Other (tell us in the comments!)'],
                ],
                'allow_multiple' => false,
                'show_results_before_vote' => false,
                'ends_at' => now()->addMonths(3)->toIso8601String(),
            ],
            'author_id' => $authorId,
            'category_slugs' => ['entertainment'],
        ]);
    }

    private function createPost(array $data): void
    {
        $categorySlugs = $data['category_slugs'] ?? [];
        unset($data['category_slugs']);

        $post = Post::create(array_merge($data, [
            'status' => 'published',
            'published_at' => now(),
            'reading_time' => 2,
        ]));

        if (!empty($categorySlugs)) {
            $categoryIds = Category::whereIn('slug', $categorySlugs)->pluck('id');
            $post->categories()->sync($categoryIds);
        }
    }
}
