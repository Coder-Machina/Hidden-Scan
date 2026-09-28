<?php

namespace App\Console\Commands;

use App\Models\Artist;
use App\Models\Author;
use App\Models\Chapter;
use App\Models\Genre;
use App\Models\Manga;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Console\Command;

class ExportProductionData extends Command
{
    protected $signature = 'app:export-production-data';
    protected $description = 'Export current database content into ProductionDataSeeder.php';

    public function handle()
    {
        $this->info('Exporting database to ProductionDataSeeder.php...');

        $users = User::with('roles')->get()->map(function ($u) {
            return [
                'name' => $u->name,
                'email' => $u->email,
                'password' => $u->password,
                'email_verified_at' => $u->email_verified_at?->toDateTimeString(),
                'roles' => $u->roles->pluck('name')->toArray(),
            ];
        })->toArray();

        $authors = Author::all()->map(fn ($a) => ['name' => $a->name, 'slug' => $a->slug])->toArray();
        $artists = Artist::all()->map(fn ($a) => ['name' => $a->name, 'slug' => $a->slug])->toArray();
        $genres = Genre::all()->map(fn ($g) => ['name' => $g->name, 'slug' => $g->slug])->toArray();
        $tags = Tag::all()->map(fn ($t) => ['name' => $t->name, 'slug' => $t->slug])->toArray();

        $mangas = Manga::with(['genres', 'tags', 'authors', 'artists', 'chapters'])->get()->map(function ($m) {
            return [
                'title' => $m->title,
                'slug' => $m->slug,
                'synopsis' => $m->synopsis,
                'type' => $m->type?->value ?? (string) $m->type,
                'status' => $m->status?->value ?? (string) $m->status,
                'release_year' => $m->release_year,
                'cover_image' => $m->cover_image,
                'banner_image' => $m->banner_image,
                'is_featured' => (bool) $m->is_featured,
                'views_count' => (int) $m->views_count,
                'average_rating' => (float) $m->average_rating,
                'ratings_count' => (int) $m->ratings_count,
                'genres' => $m->genres->pluck('slug')->toArray(),
                'tags' => $m->tags->pluck('slug')->toArray(),
                'authors' => $m->authors->pluck('slug')->toArray(),
                'artists' => $m->artists->pluck('slug')->toArray(),
                'chapters' => $m->chapters->map(function ($c) {
                    return [
                        'number' => $c->number,
                        'title' => $c->title,
                        'slug' => $c->slug,
                        'status' => $c->status?->value ?? (string) $c->status,
                        'views_count' => (int) $c->views_count,
                        'published_at' => $c->published_at?->toDateTimeString(),
                    ];
                })->toArray(),
            ];
        })->toArray();

        $usersCode = var_export($users, true);
        $authorsCode = var_export($authors, true);
        $artistsCode = var_export($artists, true);
        $genresCode = var_export($genres, true);
        $tagsCode = var_export($tags, true);
        $mangasCode = var_export($mangas, true);

        $template = <<<PHP
<?php

namespace Database\Seeders;

use App\Models\Artist;
use App\Models\Author;
use App\Models\Chapter;
use App\Models\Genre;
use App\Models\Manga;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductionDataSeeder extends Seeder
{
    public function run(): void
    {
        \$this->call([
            RoleSeeder::class,
            RolesAndPermissionsSeeder::class,
        ]);

        \$users = {$usersCode};
        foreach (\$users as \$uData) {
            \$roles = \$uData['roles'] ?? [];
            unset(\$uData['roles']);

            \Illuminate\Support\Facades\DB::table('users')->updateOrInsert(
                ['email' => \$uData['email']],
                array_merge(\$uData, ['updated_at' => now(), 'created_at' => now()])
            );

            \$user = User::where('email', \$uData['email'])->first();
            if (\$user && !empty(\$roles)) {
                try {
                    foreach (\$roles as \$roleName) {
                        \Spatie\Permission\Models\Role::firstOrCreate(['name' => \$roleName, 'guard_name' => 'web']);
                    }
                    \$user->syncRoles(\$roles);
                } catch (\Throwable \$e) {
                    \Illuminate\Support\Facades\Log::warning("Could not sync roles for user {\$user->email}: " . \$e->getMessage());
                }
            }
        }

        \$authors = {$authorsCode};
        foreach (\$authors as \$aData) {
            Author::firstOrCreate(['slug' => \$aData['slug']], \$aData);
        }

        \$artists = {$artistsCode};
        foreach (\$artists as \$artData) {
            Artist::firstOrCreate(['slug' => \$artData['slug']], \$artData);
        }

        \$genres = {$genresCode};
        foreach (\$genres as \$gData) {
            Genre::firstOrCreate(['slug' => \$gData['slug']], \$gData);
        }

        \$tags = {$tagsCode};
        foreach (\$tags as \$tData) {
            Tag::firstOrCreate(['slug' => \$tData['slug']], \$tData);
        }

        \$mangas = {$mangasCode};
        foreach (\$mangas as \$mData) {
            \$genreSlugs = \$mData['genres'] ?? [];
            \$tagSlugs = \$mData['tags'] ?? [];
            \$authorSlugs = \$mData['authors'] ?? [];
            \$artistSlugs = \$mData['artists'] ?? [];
            \$chapters = \$mData['chapters'] ?? [];

            unset(\$mData['genres'], \$mData['tags'], \$mData['authors'], \$mData['artists'], \$mData['chapters']);

            \$manga = Manga::updateOrCreate(
                ['slug' => \$mData['slug']],
                \$mData
            );

            if (!empty(\$genreSlugs)) {
                \$genreIds = Genre::whereIn('slug', \$genreSlugs)->pluck('id');
                \$manga->genres()->sync(\$genreIds);
            }

            if (!empty(\$tagSlugs)) {
                \$tagIds = Tag::whereIn('slug', \$tagSlugs)->pluck('id');
                \$manga->tags()->sync(\$tagIds);
            }

            if (!empty(\$authorSlugs)) {
                \$authorIds = Author::whereIn('slug', \$authorSlugs)->pluck('id');
                \$manga->authors()->sync(\$authorIds);
            }

            if (!empty(\$artistSlugs)) {
                \$artistIds = Artist::whereIn('slug', \$artistSlugs)->pluck('id');
                \$manga->artists()->sync(\$artistIds);
            }

            foreach (\$chapters as \$cData) {
                Chapter::updateOrCreate(
                    [
                        'manga_id' => \$manga->id,
                        'number' => \$cData['number'],
                    ],
                    \$cData
                );
            }
        }
    }
}
PHP;

        file_put_contents(database_path('seeders/ProductionDataSeeder.php'), $template);
        $this->info('ProductionDataSeeder.php successfully generated!');
    }
}
