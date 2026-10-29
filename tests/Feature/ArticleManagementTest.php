<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ArticleManagementTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function payload(Section $section, array $overrides = []): array
    {
        return array_merge([
            'title' => 'Panduan Belajar CPNS',
            'section_id' => $section->id,
            'excerpt' => 'Belajar secara konsisten.',
            'body' => '<h2>Langkah awal</h2><p>Belajar <strong>rutin</strong> dan <em>terarah</em>.</p>',
            'status' => 'draft',
        ], $overrides);
    }

    public function test_admin_can_create_draft_with_formatted_content_and_server_owned_author(): void
    {
        $admin = User::factory()->admin()->create();
        $section = Section::factory()->create();

        $this->actingAs($admin)->post(route('admin.articles.store'), $this->payload($section, ['created_by' => 999, 'published_at' => now(), 'cover_path' => 'unsafe.php']))
            ->assertSessionHasNoErrors();

        $article = Article::sole();
        $this->assertDatabaseHas('articles', [
            'id' => $article->id, 'slug' => 'panduan-belajar-cpns', 'created_by' => $admin->id,
            'status' => 'draft', 'published_at' => null, 'cover_path' => null,
            'body' => '<h2>Langkah awal</h2><p>Belajar <strong>rutin</strong> dan <em>terarah</em>.</p>',
        ]);
        $this->get(route('admin.articles.edit', $article))->assertSee('Preview langsung')->assertSee('data-editor-content', false);
    }

    public function test_article_can_be_published_edited_and_returned_to_draft(): void
    {
        $this->travelTo(now()->startOfSecond());
        $article = Article::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put(route('admin.articles.update', $article), $this->payload($article->section, ['title' => $article->title, 'slug' => $article->slug, 'status' => 'published']))
            ->assertRedirect(route('admin.articles.edit', $article))->assertSessionHasNoErrors();
        $this->assertSame(ArticleStatus::Published, $article->fresh()->status);
        $this->assertTrue($article->fresh()->published_at->equalTo(now()));

        $publishedAt = $article->fresh()->published_at;
        $this->travel(1)->hours();
        $this->put(route('admin.articles.update', $article), $this->payload($article->section, ['status' => 'published', 'body' => '<p><u>Teks revisi</u></p>']))
            ->assertSessionHasNoErrors();
        $this->assertTrue($article->fresh()->published_at->equalTo($publishedAt));
        $this->assertDatabaseHas('articles', ['id' => $article->id, 'body' => '<p><u>Teks revisi</u></p>', 'updated_by' => $admin->id]);

        $this->put(route('admin.articles.update', $article), $this->payload($article->section, ['status' => 'draft']))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('articles', ['id' => $article->id, 'status' => 'draft', 'published_at' => null]);
    }

    #[DataProvider('emptyPublishedBodies')]
    public function test_publishing_rejects_content_without_readable_text(string $body): void
    {
        $section = Section::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.articles.store'), $this->payload($section, ['status' => 'published', 'body' => $body]))
            ->assertSessionHasErrors(['body' => 'Isi artikel tidak boleh kosong saat diterbitkan.']);
        $this->assertDatabaseCount('articles', 0);
    }

    public static function emptyPublishedBodies(): array
    {
        return [
            'blank' => [''],
            'empty formatting' => ['<p><strong>&nbsp;</strong><br></p>'],
            'script only' => ['<script>alert(1)</script>'],
            'image only' => ['<img src="x" onerror="alert(1)">'],
        ];
    }

    public function test_empty_draft_can_be_saved(): void
    {
        $section = Section::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.articles.store'), $this->payload($section, ['body' => '']))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('articles', ['status' => 'draft', 'body' => '']);
    }

    public function test_article_content_removes_active_html_unsafe_links_and_styles_but_keeps_formatting(): void
    {
        $section = Section::factory()->create();
        $body = '<script>alert(1)</script><svg onload="alert(2)"><text>bad</text></svg><p onclick="alert(3)" style="text-align:center;position:fixed;background-image:url(javascript:alert(1))">Pancasila <strong>tebal</strong> <em>miring</em> <u>garis</u> <s>coret</s></p><font size="5" color="#0191ed">Warna</font><a href="javascript:alert(4)">Bahaya</a><a href="https://example.com" target="_blank">Aman</a><table><tr><td colspan="2">Tabel</td></tr></table>';

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.articles.store'), $this->payload($section, ['body' => $body, 'status' => 'published']))->assertSessionHasNoErrors();

        $article = Article::sole();
        $this->assertStringContainsString('<strong>tebal</strong>', $article->body);
        $this->assertStringContainsString('text-align:center;', $article->body);
        $this->assertStringContainsString('font-size:24px;', $article->body);
        $this->assertStringNotContainsString('alert', $article->body);
        $this->assertStringNotContainsString('position', $article->body);
        $this->get(route('articles.show', $article))
            ->assertSee('<strong>tebal</strong>', false)->assertSee('<em>miring</em>', false)
            ->assertSee('<u>garis</u>', false)->assertSee('<s>coret</s>', false)
            ->assertSee('https://example.com', false)->assertSee('colspan="2"', false)
            ->assertDontSee('javascript:', false)->assertDontSee('<script>alert', false);
    }

    public function test_guest_and_participant_cannot_manage_articles(): void
    {
        $article = Article::factory()->create();

        $this->get(route('admin.articles.create'))->assertRedirect(route('login'));
        $this->post(route('admin.articles.store'), [])->assertRedirect(route('login'));
        $this->post(route('admin.articles.images.store'), [])->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('admin.articles.index'))->assertForbidden();
        $this->post(route('admin.articles.store'), [])->assertForbidden();
        $this->postJson(route('admin.articles.images.store'), [])->assertForbidden();
        $this->put(route('admin.articles.update', $article), [])->assertForbidden();
        $this->delete(route('admin.articles.destroy', $article))->assertForbidden();
        $this->assertModelExists($article);
        $this->assertDatabaseCount('articles', 1);
    }

    public function test_member_listing_and_details_hide_drafts_inactive_deleted_and_future_content(): void
    {
        $this->travelTo(now()->startOfSecond());
        $visible = Article::factory()->published()->create(['title' => 'Artikel Terbit']);
        $draft = Article::factory()->create(['title' => 'Rahasia Draft']);
        $inactive = Article::factory()->published()->for(Section::factory()->inactive())->create(['title' => 'Bidang Nonaktif']);
        $future = Article::factory()->published()->create(['title' => 'Artikel Mendatang', 'published_at' => now()->addDay()]);
        $deleted = Article::factory()->published()->create(['title' => 'Artikel Dihapus']);
        $deleted->delete();
        $removedSection = Article::factory()->published()->create(['title' => 'Bidang Dihapus']);
        $removedSection->section->delete();

        $this->actingAs(User::factory()->create())->get(route('articles.index'))
            ->assertSee('Artikel Terbit')->assertDontSee('Rahasia Draft')->assertDontSee('Bidang Nonaktif')
            ->assertDontSee('Artikel Mendatang')->assertDontSee('Artikel Dihapus')->assertDontSee('Bidang Dihapus');
        $this->get(route('articles.show', $visible))->assertSee($visible->title);
        foreach ([$draft, $inactive, $future, $deleted, $removedSection] as $hidden) {
            $this->get(route('articles.show', $hidden))->assertNotFound();
        }
    }

    public function test_landing_page_shows_recent_published_articles_with_read_links(): void
    {
        $oldest = Article::factory()->published()->create(['title' => 'Artikel Lama', 'published_at' => now()->subDays(4)]);
        $article = Article::factory()->published()->create(['title' => 'Panduan Belajar Terbaru', 'published_at' => now()->subDay()]);
        Article::factory()->published()->create(['title' => 'Artikel Kedua', 'published_at' => now()->subDays(2)]);
        Article::factory()->published()->create(['title' => 'Artikel Ketiga', 'published_at' => now()->subDays(3)]);
        Article::factory()->create(['title' => 'Artikel Rahasia']);

        $this->get(route('home'))
            ->assertSee('Panduan Belajar Terbaru')
            ->assertSee(route('articles.show', $article))
            ->assertSee('Artikel Kedua')
            ->assertSee('Artikel Ketiga')
            ->assertDontSee($oldest->title)
            ->assertDontSee('Artikel Rahasia');
    }

    public function test_guest_can_browse_and_read_published_articles_but_not_drafts(): void
    {
        $article = Article::factory()->published()->create(['title' => 'Panduan untuk Pengunjung', 'body' => '<p>Isi bacaan <strong>penting</strong>.</p>']);
        $draft = Article::factory()->create(['title' => 'Draft Tersembunyi']);

        $this->get(route('articles.index'))->assertSee($article->title)->assertDontSee($draft->title);
        $this->get(route('articles.show', $article))->assertSee($article->title)->assertSee('<strong>penting</strong>', false)
            ->assertSee('Navigasi utama')->assertSee('Artikel berikutnya akan muncul')->assertDontSee('Pratinjau admin');
        $this->get(route('articles.show', $draft))->assertNotFound();
    }

    public function test_article_reads_count_repeat_views_once_per_reader_and_ignore_admin_preview(): void
    {
        $article = Article::factory()->published()->create();
        $draft = Article::factory()->create();

        $this->get(route('articles.show', $article))->assertOk();
        $reader = User::factory()->create();
        $this->actingAs($reader)->get(route('articles.show', $article))->assertOk();
        $this->get(route('articles.show', $article))->assertOk();
        $this->assertDatabaseCount('article_views', 3);
        $this->assertSame(2, DB::table('article_views')->distinct()->count('reader_hash'));

        $this->actingAs(User::factory()->create())->get(route('articles.show', $article))->assertOk();
        $this->assertSame(3, DB::table('article_views')->distinct()->count('reader_hash'));

        $this->call('HEAD', route('articles.show', $article))->assertOk();
        $this->actingAs(User::factory()->admin()->create())->get(route('articles.show', $article))->assertSee('Pratinjau admin');
        $this->get(route('articles.show', $draft))->assertSee('Pratinjau admin');
        $this->assertDatabaseCount('article_views', 4);
    }

    public function test_reader_recommends_only_three_other_published_articles(): void
    {
        $article = Article::factory()->published()->create(['title' => 'Artikel Utama']);
        Article::factory()->published()->create(['title' => 'Artikel Satu', 'published_at' => now()->subDay()]);
        Article::factory()->published()->create(['title' => 'Artikel Dua', 'published_at' => now()->subDays(2)]);
        Article::factory()->published()->create(['title' => 'Artikel Tiga', 'published_at' => now()->subDays(3)]);
        Article::factory()->published()->create(['title' => 'Artikel Lama', 'published_at' => now()->subDays(4)]);
        Article::factory()->create(['title' => 'Rekomendasi Draft']);
        Article::factory()->published()->create(['title' => 'Rekomendasi Mendatang', 'published_at' => now()->addDay()]);
        Article::factory()->published()->for(Section::factory()->inactive())->create(['title' => 'Rekomendasi Nonaktif']);

        $this->get(route('articles.show', $article))
            ->assertSee('Artikel lainnya')->assertSee('Artikel Satu')->assertSee('Artikel Dua')->assertSee('Artikel Tiga')
            ->assertDontSee('Artikel Lama')->assertDontSee('Rekomendasi Draft')
            ->assertDontSee('Rekomendasi Mendatang')->assertDontSee('Rekomendasi Nonaktif');
    }

    public function test_inactive_admin_cannot_preview_draft_on_public_article_route(): void
    {
        $article = Article::factory()->create();

        $this->actingAs(User::factory()->admin()->inactive()->create())
            ->get(route('articles.show', $article))->assertNotFound();
    }

    public function test_admin_can_preview_draft_and_escaped_metadata(): void
    {
        $title = 'Judul <script>alert("x")</script>';
        $article = Article::factory()->create(['title' => $title, 'excerpt' => '<img onerror="evil()">']);

        $this->actingAs(User::factory()->admin()->create())->get(route('articles.show', $article))
            ->assertSee('Pratinjau admin')->assertSee($title)->assertDontSee($title, false)
            ->assertSee($article->excerpt)->assertDontSee($article->excerpt, false);
        $this->get(route('admin.articles.edit', $article))->assertSee($title)->assertDontSee($title, false);
    }

    public function test_editor_preview_keeps_the_original_author_when_another_admin_edits(): void
    {
        $author = User::factory()->admin()->create(['name' => 'Penulis Artikel Asli']);
        $article = Article::factory()->create(['created_by' => $author->id]);

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.articles.edit', $article))
            ->assertSee('Penulis Artikel Asli');
    }

    public function test_admin_listing_search_and_filters_use_saved_data_with_pagination(): void
    {
        $section = Section::factory()->create(['name' => 'Wawasan']);
        $wanted = Article::factory()->published()->for($section)->create(['title' => 'Sejarah Pilihan']);
        Article::factory()->for($section)->create(['title' => 'Sejarah Draft']);
        Article::factory()->published()->create(['title' => 'Sejarah Bidang Lain']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.articles.index', ['q' => 'Sejarah', 'section_id' => $section->id, 'status' => 'published']))
            ->assertSee($wanted->title)->assertDontSee('Sejarah Draft')->assertDontSee('Sejarah Bidang Lain')
            ->assertViewHas('articles', fn ($articles): bool => $articles->total() === 1)
            ->assertViewHas('stats', fn (array $stats): bool => $stats === ['total' => 3, 'published' => 2, 'draft' => 1, 'readers' => 0, 'views' => 0]);
    }

    public function test_admin_article_list_shows_readers_and_views_for_each_article(): void
    {
        $popularArticle = Article::factory()->published()->create(['title' => 'Artikel Populer']);
        Article::factory()->published()->create(['title' => 'Artikel Baru']);
        DB::table('article_views')->insert([
            ['article_id' => $popularArticle->id, 'reader_hash' => str_repeat('a', 64), 'created_at' => now()],
            ['article_id' => $popularArticle->id, 'reader_hash' => str_repeat('a', 64), 'created_at' => now()],
            ['article_id' => $popularArticle->id, 'reader_hash' => str_repeat('b', 64), 'created_at' => now()],
        ]);

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.articles.index'))
            ->assertSee('Pembaca Unik')->assertSee('Tayangan')
            ->assertViewHas('stats', fn (array $stats): bool => $stats['readers'] === 2 && $stats['views'] === 3)
            ->assertViewHas('articleViews', fn ($articleViews): bool => (int) $articleViews[$popularArticle->id]->readers_count === 2 && (int) $articleViews[$popularArticle->id]->views_count === 3);
    }

    public function test_slug_generation_does_not_reuse_archived_slugs(): void
    {
        $archived = Article::factory()->create(['slug' => 'panduan-belajar-cpns']);
        $archived->delete();
        $section = Section::factory()->create();

        $this->actingAs(User::factory()->admin()->create())->post(route('admin.articles.store'), $this->payload($section))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('articles', ['slug' => 'panduan-belajar-cpns-2']);
    }

    public function test_cover_upload_replace_and_remove_keep_files_in_sync(): void
    {
        Storage::fake('public');
        $section = Section::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.articles.store'), $this->payload($section, ['cover' => UploadedFile::fake()->image('cover.jpg'), 'cover_source' => 'Fotografer pertama', 'cover_source_url' => 'https://example.com/first']))
            ->assertSessionHasNoErrors();
        $article = Article::sole();
        $this->assertDatabaseHas('articles', ['id' => $article->id, 'cover_source' => 'Fotografer pertama', 'cover_source_url' => 'https://example.com/first']);
        $firstPath = $article->cover_path;
        Storage::disk('public')->assertExists($firstPath);

        $this->put(route('admin.articles.update', $article), $this->payload($section, ['cover' => UploadedFile::fake()->image('new.png'), 'cover_source' => 'Dokumentasi pribadi']))
            ->assertSessionHasNoErrors();
        $secondPath = $article->fresh()->cover_path;
        Storage::disk('public')->assertExists($secondPath);
        Storage::disk('public')->assertMissing($firstPath);
        $this->assertDatabaseHas('articles', ['id' => $article->id, 'cover_source' => 'Dokumentasi pribadi', 'cover_source_url' => null]);

        $this->put(route('admin.articles.update', $article), $this->payload($section, ['remove_cover' => 1]))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('articles', ['id' => $article->id, 'cover_path' => null, 'cover_source' => null, 'cover_source_url' => null]);
        Storage::disk('public')->assertMissing($secondPath);
    }

    public function test_non_image_cover_is_rejected_without_saving_an_article(): void
    {
        Storage::fake('public');
        $section = Section::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.articles.store'), $this->payload($section, ['cover' => UploadedFile::fake()->create('cover.pdf', 10, 'application/pdf')]))
            ->assertSessionHasErrors('cover');
        $this->assertDatabaseCount('articles', 0);
        Storage::disk('public')->assertDirectoryEmpty('articles/covers');
    }

    public function test_deleting_an_article_archives_it_and_preserves_its_cover(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('articles/covers/keep.jpg', 'example');
        $article = Article::factory()->create(['cover_path' => 'articles/covers/keep.jpg']);

        $this->actingAs(User::factory()->admin()->create())->delete(route('admin.articles.destroy', $article))
            ->assertRedirect(route('admin.articles.index'));
        $this->assertSoftDeleted($article);
        Storage::disk('public')->assertExists('articles/covers/keep.jpg');
        $this->get(route('admin.articles.edit', $article))->assertNotFound();
    }

    public function test_invalid_article_fields_are_rejected_without_a_server_error(): void
    {
        $section = Section::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.articles.store'), $this->payload($section, ['title' => ['array'], 'section_id' => 99999, 'status' => 'invalid', 'body' => ['array']]))
            ->assertSessionHasErrors(['title', 'section_id', 'status', 'body']);
        $this->assertDatabaseCount('articles', 0);
    }

    public function test_search_rejects_array_input(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->getJson(route('admin.articles.index', ['q' => ['bad']]))->assertUnprocessable()->assertJsonValidationErrors('q');
    }

    public function test_admin_can_upload_inline_image_and_publish_it_with_escaped_credit_and_alt_text(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $section = Section::factory()->create();
        $image = UploadedFile::fake()->image('study.png');
        $path = 'articles/content/'.$image->hashName();
        $url = Storage::disk('public')->url($path);

        $this->actingAs($admin)->postJson(route('admin.articles.images.store'), ['image' => $image])
            ->assertCreated()->assertJsonPath('url', $url);

        Storage::disk('public')->assertExists($path);
        $this->assertDatabaseCount('articles', 0);

        $body = '<p>Panduan belajar.</p><figure contenteditable="false" onclick="evil()"><img src="'.$url.'" alt="Buku &quot;CPNS&quot;" onerror="evil()"><figcaption>Sumber: <a href="https://example.com/photo" onclick="evil()">Foto &lt;script&gt;evil()&lt;/script&gt;</a></figcaption></figure>';
        $this->post(route('admin.articles.store'), $this->payload($section, ['body' => $body, 'status' => 'published']))->assertSessionHasNoErrors();

        $article = Article::sole();
        $this->assertStringContainsString('<figure><img src="'.$url.'" alt="Buku &quot;CPNS&quot;" loading="lazy">', $article->body);
        $this->assertStringNotContainsString('onerror', $article->body);
        $this->assertStringNotContainsString('onclick', $article->body);
        $this->assertStringNotContainsString('contenteditable', $article->body);
        $this->get(route('admin.articles.edit', $article))->assertSee('data-image-dialog', false)->assertSee('Sumber gambar cover');
        $this->get(route('articles.show', $article))
            ->assertSee('<figure><img src="'.$url.'"', false)
            ->assertSee('<figcaption>Sumber: <a href="https://example.com/photo"', false)
            ->assertSee('Foto &lt;script&gt;evil()&lt;/script&gt;', false)
            ->assertDontSee('<script>evil()</script>', false);
    }

    #[DataProvider('invalidInlineImages')]
    public function test_inline_image_upload_rejects_invalid_files_without_storing_them(string $name, string $mimeType, int $size): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->admin()->create())
            ->postJson(route('admin.articles.images.store'), ['image' => UploadedFile::fake()->create($name, $size, $mimeType)])
            ->assertUnprocessable()->assertJsonValidationErrors('image');

        Storage::disk('public')->assertDirectoryEmpty('articles/content');
    }

    public static function invalidInlineImages(): array
    {
        return [
            'document' => ['file.pdf', 'application/pdf', 10],
            'svg' => ['file.svg', 'image/svg+xml', 10],
            'gif' => ['file.gif', 'image/gif', 10],
            'oversized image' => ['file.jpg', 'image/jpeg', 5121],
        ];
    }

    #[DataProvider('unsafeInlineImageUrls')]
    public function test_article_removes_unsafe_image_urls_and_unsafe_credit_links(string $url): void
    {
        $section = Section::factory()->create();
        $body = '<p>Isi artikel.</p><figure><img src="'.e($url).'" onerror="evil()"><figcaption>Sumber: <a href="javascript:evil()">Pemilik foto</a></figcaption></figure>';

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.articles.store'), $this->payload($section, ['body' => $body]))->assertSessionHasNoErrors();

        $this->assertSame('<p>Isi artikel.</p><figure><figcaption>Sumber: <a>Pemilik foto</a></figcaption></figure>', Article::sole()->body);
    }

    public static function unsafeInlineImageUrls(): array
    {
        return [
            'javascript' => ['javascript:evil()'],
            'data svg' => ['data:image/svg+xml,<svg onload="evil()"/>'],
            'blob preview' => ['blob:https://example.com/123'],
            'protocol relative' => ['//example.com/image.jpg'],
            'backslash' => ['/\\example.com/image.jpg'],
            'control character' => ["https://example.com/\nimage.jpg"],
        ];
    }

    public function test_local_inline_image_and_plain_source_survive_editing(): void
    {
        $article = Article::factory()->create();
        $body = '<p>Belajar.</p><figure><img src="/storage/articles/content/study.jpg" alt="Buku"><figcaption>Sumber: Dokumentasi pribadi</figcaption></figure>';

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.articles.update', $article), $this->payload($article->section, ['body' => $body]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('articles', ['id' => $article->id, 'body' => '<p>Belajar.</p><figure><img src="/storage/articles/content/study.jpg" alt="Buku" loading="lazy"><figcaption>Sumber: Dokumentasi pribadi</figcaption></figure>']);
    }

    public function test_cover_credits_are_escaped_and_visible_wherever_the_cover_is_displayed(): void
    {
        $credit = 'Fotografer <script>evil()</script>';
        $article = Article::factory()->published()->create(['cover_path' => 'articles/covers/photo.jpg', 'cover_source' => $credit, 'cover_source_url' => 'https://example.com/photo']);

        foreach (['home', 'articles.index'] as $route) {
            $this->get(route($route))->assertSee($credit)->assertDontSee($credit, false)->assertSee('href="https://example.com/photo"', false);
        }
        $this->get(route('articles.show', $article))
            ->assertSee($credit)->assertDontSee($credit, false)->assertSee('href="https://example.com/photo"', false);
    }

    public function test_admin_can_edit_and_clear_cover_credit_without_replacing_the_image(): void
    {
        $article = Article::factory()->create(['cover_path' => 'articles/covers/keep.jpg', 'cover_source' => 'Sumber lama']);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.articles.update', $article), $this->payload($article->section, ['cover_source' => 'Sumber baru', 'cover_source_url' => 'https://example.com/new']))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('articles', ['id' => $article->id, 'cover_path' => 'articles/covers/keep.jpg', 'cover_source' => 'Sumber baru', 'cover_source_url' => 'https://example.com/new']);

        $this->put(route('admin.articles.update', $article), $this->payload($article->section, ['cover_source' => '', 'cover_source_url' => '']))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('articles', ['id' => $article->id, 'cover_path' => 'articles/covers/keep.jpg', 'cover_source' => null, 'cover_source_url' => null]);
    }

    public function test_unsafe_cover_credit_url_is_rejected_without_saving_changes(): void
    {
        $article = Article::factory()->create(['cover_path' => 'articles/covers/keep.jpg', 'cover_source_url' => 'https://example.com/original']);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.articles.update', $article), $this->payload($article->section, ['cover_source_url' => 'javascript:evil()']))
            ->assertSessionHasErrors('cover_source_url');

        $this->assertDatabaseHas('articles', ['id' => $article->id, 'cover_source_url' => 'https://example.com/original']);
    }

    public function test_scheduled_article_is_hidden_until_the_selected_wita_time(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 29)->setTime(0, 0));
        $section = Section::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.articles.store'), $this->payload($section, ['status' => 'published', 'publish_at' => '2026-10-30T10:15']))
            ->assertSessionHasNoErrors();

        $article = Article::sole();
        $this->assertSame('2026-10-30 02:15:00', $article->published_at->copy()->utc()->format('Y-m-d H:i:s'));
        $this->get(route('admin.articles.index'))->assertSee('Terjadwal');
        $this->get(route('articles.show', $article))->assertSee('Dijadwalkan');

        $this->actingAs(User::factory()->create());
        $this->get(route('articles.index'))->assertDontSee($article->title);
        $this->get(route('articles.show', $article))->assertNotFound();

        $this->travelTo($article->published_at);
        $this->get(route('articles.index'))->assertSee($article->title);
        $this->get(route('articles.show', $article))->assertSee($article->title);
    }

    public function test_clearing_a_schedule_publishes_now_and_saving_draft_does_not_publish(): void
    {
        $this->freezeTime();
        $article = Article::factory()->published()->create(['published_at' => now()->addDay()]);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.articles.update', $article), $this->payload($article->section, ['status' => 'published', 'publish_at' => '']))
            ->assertSessionHasNoErrors();
        $this->assertTrue($article->fresh()->published_at->equalTo(now()->startOfSecond()));

        $this->put(route('admin.articles.update', $article), $this->payload($article->section, ['publish_at' => '2026-12-31T09:00']))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('articles', ['id' => $article->id, 'status' => 'draft', 'published_at' => null]);
    }

    #[DataProvider('invalidPublicationTimes')]
    public function test_invalid_publication_time_does_not_change_the_article(mixed $time): void
    {
        $article = Article::factory()->create(['title' => 'Judul tersimpan']);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.articles.update', $article), $this->payload($article->section, ['publish_at' => $time]))
            ->assertSessionHasErrors('publish_at');

        $this->assertDatabaseHas('articles', ['id' => $article->id, 'title' => 'Judul tersimpan', 'published_at' => null]);
    }

    public static function invalidPublicationTimes(): array
    {
        return [
            'invalid date' => ['2026-02-30T10:00'],
            'invalid hour' => ['2026-10-30T25:00'],
            'ambiguous format' => ['30/10/2026 10:00'],
            'array' => [['2026-10-30T10:00']],
        ];
    }

    public function test_advanced_article_blocks_survive_saving_editing_and_reader_rendering(): void
    {
        $section = Section::factory()->create();
        $body = '<h2>Rumus</h2><p>H<sub>2</sub>O dan x<sup>2</sup> <mark>penting</mark></p>'
            .'<div data-callout="tip"><p><strong>Tips belajar</strong></p><p>Belajar rutin.</p></div>'
            .'<details><summary>Mengapa?</summary><p>Penjelasan lengkap.</p></details>'
            .'<pre><code>&lt;script&gt;contoh()&lt;/script&gt;</code></pre>'
            .'<ol start="5"><li>Langkah kelima</li></ol>'
            .'<table><caption>Jadwal belajar</caption><tbody><tr><th>Hari</th><th>Materi</th></tr><tr><td>Senin</td><td>TWK</td></tr></tbody></table>'
            .'<figure data-width="50" data-align="right"><img src="/storage/articles/content/study.jpg" alt="Buku"><figcaption><p>Buku latihan.</p><p>Sumber: Dokumentasi pribadi</p></figcaption></figure>'
            .'<h2>Referensi</h2><p><a href="https://example.com" target="_blank">Baca referensi</a></p>';

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.articles.store'), $this->payload($section, ['status' => 'published', 'body' => $body]))
            ->assertSessionHasNoErrors();

        $article = Article::sole();
        $storedBody = $article->body;
        $this->assertStringContainsString('<div data-callout="tip">', $storedBody);
        $this->assertStringContainsString('<figure data-align="right" data-width="50">', $storedBody);
        $this->assertStringContainsString('rel="noopener noreferrer" target="_blank"', $storedBody);
        $this->assertStringNotContainsString('<script>', $storedBody);
        $this->get(route('articles.show', $article))->assertSee($storedBody, false)->assertSee('Cetak / Simpan PDF')->assertSee('Ukuran teks');

        $this->put(route('admin.articles.update', $article), $this->payload($section, ['body' => $storedBody]))->assertSessionHasNoErrors();
        $this->assertSame($storedBody, $article->fresh()->body);
    }

    public function test_advanced_blocks_discard_unsafe_attributes_and_unapproved_layout_values(): void
    {
        $section = Section::factory()->create();
        $body = '<div data-callout="danger" class="fixed" onclick="evil()">Informasi</div>'
            .'<figure data-width="999" data-align="fixed" style="position:fixed"><img src="/storage/safe.jpg" onerror="evil()"></figure>'
            .'<details ontoggle="evil()" open><summary onclick="evil()">Tanya</summary><p>Jawab</p></details>'
            .'<ol start="999999"><li>Langkah</li></ol><a href="javascript:evil()" target="_blank">Link</a>';

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.articles.store'), $this->payload($section, ['body' => $body]))->assertSessionHasNoErrors();

        $this->assertSame('<div>Informasi</div><figure><img src="/storage/safe.jpg" alt="" loading="lazy"></figure><details><summary>Tanya</summary><p>Jawab</p></details><ol><li>Langkah</li></ol><a>Link</a>', Article::sole()->body);
    }

    public function test_reading_time_counts_table_cells_code_and_closed_answers_as_separate_words(): void
    {
        $article = Article::factory()->make(['body' => '<table><tr>'.str_repeat('<td>kata</td>', 100).'</tr></table><pre>'.str_repeat('kode ', 50).'</pre><details><summary>Tanya</summary><p>'.str_repeat('jawab ', 50).'</p></details>']);

        $this->assertSame(2, $article->readingMinutes());
    }

    public function test_mixed_nested_numbering_survives_saving_reopening_and_reader_rendering(): void
    {
        $section = Section::factory()->create();
        $body = '<ol type="1" data-list-type="1" data-list-suffix="period"><li>UTAMA<ol type="1" data-list-type="1" data-list-suffix="paren"><li>sub 1</li><li>sub 2</li></ol></li>'
            .'<li>UTAMA<ol type="a" data-list-type="a" data-list-suffix="paren"><li>sub a</li><li>sub b</li></ol></li></ol>';

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.articles.store'), $this->payload($section, ['status' => 'published', 'body' => $body]))
            ->assertSessionHasNoErrors();

        $article = Article::sole();
        $this->assertSame($body, $article->body);
        $this->get(route('articles.show', $article))->assertSee($body, false);
        $this->get(route('admin.articles.edit', $article))->assertViewHas('article', fn (Article $saved): bool => $saved->safeBody() === $body);

        $this->put(route('admin.articles.update', $article), $this->payload($section, ['status' => 'published', 'body' => $article->safeBody()]))
            ->assertSessionHasNoErrors();
        $this->assertSame($body, $article->fresh()->body);
    }

    #[DataProvider('supportedNumberingFormats')]
    public function test_numbering_keeps_its_format_start_number_and_item_value(string $type, string $suffix): void
    {
        $section = Section::factory()->create();
        $body = '<ol type="'.$type.'" data-list-suffix="'.$suffix.'" start="27"><li value="28">Langkah berikutnya</li><li>Lanjutan</li></ol>';

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.articles.store'), $this->payload($section, ['body' => $body]))->assertSessionHasNoErrors();

        $this->assertSame('<ol type="'.$type.'" data-list-type="'.$type.'" data-list-suffix="'.$suffix.'" start="27"><li value="28">Langkah berikutnya</li><li>Lanjutan</li></ol>', Article::sole()->body);
    }

    public static function supportedNumberingFormats(): array
    {
        return [
            'decimal period' => ['1', 'period'], 'decimal parentheses' => ['1', 'paren'],
            'lower alpha period' => ['a', 'period'], 'lower alpha parentheses' => ['a', 'paren'],
            'upper alpha period' => ['A', 'period'], 'upper alpha parentheses' => ['A', 'paren'],
            'lower roman period' => ['i', 'period'], 'lower roman parentheses' => ['i', 'paren'],
            'upper roman period' => ['I', 'period'], 'upper roman parentheses' => ['I', 'paren'],
        ];
    }

    public function test_numbering_discards_invalid_formats_and_unsafe_attributes(): void
    {
        $section = Section::factory()->create();
        $body = '<ol type="javascript:evil()" data-list-suffix="url(evil)" start="10000" onclick="evil()"><li value="-1" style="list-style-image:url(evil)">Satu</li><li value="1.5">Dua</li></ol>';

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.articles.store'), $this->payload($section, ['body' => $body]))->assertSessionHasNoErrors();

        $this->assertSame('<ol><li>Satu</li><li>Dua</li></ol>', Article::sole()->body);
    }
}
