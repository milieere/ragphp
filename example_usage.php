<?php

require_once 'vendor/autoload.php';
require_once 'src/prompt_registry.php';

// Initialize database connection (do this once at the start of your application)
PromptRegistry::initialize(
    host: 'localhost',
    database: 'php_practice',
    username: 'root',
    password: ''  // Change these to your MySQL credentials
);

// Create a new registry instance
$registry = new PromptRegistry();

// Add some prompts
$registry->addPrompt('Write a story about a brave knight', 'story_prompt');
$registry->addPrompt('Explain quantum computing in simple terms', 'explain_prompt');
$registry->addPrompt('Generate a creative business name', 'business_name_prompt');

// List all prompt names
echo "All prompts:\n";
print_r($registry->listPrompts());
echo "\n";

// Get a specific prompt
$prompt = $registry->getPrompt('story_prompt');
echo "Story prompt: $prompt\n\n";

// Refine a prompt (append to it)
$registry->refinePrompt('story_prompt', ' who fights dragons');
$refined = $registry->getPrompt('story_prompt');
echo "Refined prompt: $refined\n\n";

// Get all prompts with full details
echo "All prompts with timestamps:\n";
print_r($registry->getAllPrompts());

// Delete a prompt
$registry->deletePrompt('business_name_prompt');
echo "\nAfter deletion:\n";
print_r($registry->listPrompts());
