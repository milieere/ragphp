<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;

class Prompt extends Model {
    protected $table = 'prompts';
    protected $primaryKey = 'prompt_name';
    public $incrementing = false; // Since prompt_name is not auto-increment
    protected $keyType = 'string';

    protected $fillable = ['prompt_name', 'prompt'];
}

class PromptRegistry {
    private static bool $initialized = false;

    /**
     * Initialize database connection (call once at application start)
     */
    public static function initialize(
        string $host = 'localhost',
        string $database = 'php_practice',
        string $username = 'root',
        string $password = ''
    ): void {
        if (self::$initialized) {
            return;
        }

        $capsule = new Capsule;
        $capsule->addConnection([
            'driver' => 'mysql',
            'host' => $host,
            'database' => $database,
            'username' => $username,
            'password' => $password,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ]);

        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        self::$initialized = true;
        self::createTableIfNotExists();
    }

    /**
     * Create the prompts table if it doesn't exist
     */
    private static function createTableIfNotExists(): void {
        $schema = Capsule::schema();

        if (!$schema->hasTable('prompts')) {
            $schema->create('prompts', function ($table) {
                $table->string('prompt_name')->primary();
                $table->text('prompt');
                $table->timestamps();
            });
        }
    }

    /**
     * Add or update a prompt
     */
    public function addPrompt(string $prompt, string $promptName): void {
        Prompt::updateOrCreate(
            ['prompt_name' => $promptName],
            ['prompt' => $prompt]
        );
    }

    /**
     * List all prompt names
     */
    public function listPrompts(): array {
        return Prompt::all()->pluck('prompt_name')->toArray();
    }

    /**
     * Get a specific prompt by name
     */
    public function getPrompt(string $promptName): ?string {
        $prompt = Prompt::find($promptName);
        return $prompt ? $prompt->prompt : null;
    }

    /**
     * Refine/append to an existing prompt
     */
    public function refinePrompt(string $promptName, string $update): void {
        $prompt = Prompt::find($promptName);
        
        if ($prompt) {
            $prompt->prompt = $prompt->prompt . $update;
            $prompt->save();
        }
    }

    /**
     * Delete a prompt by name
     */
    public function deletePrompt(string $promptName): bool {
        $prompt = Prompt::find($promptName);
        return $prompt ? $prompt->delete() : false;
    }

    /**
     * Get all prompts with full details
     */
    public function getAllPrompts(): array {
        return Prompt::all()->toArray();
    }
}
