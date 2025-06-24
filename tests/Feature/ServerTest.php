<?php

namespace Tests\Feature;

use App\Services\ServeurService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ServerTest extends TestCase
{   
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    public function test_addServer(): void
    {
        $serverManager = new ServeurService();
        $serverManager->addServer([
            "hostname" => env('TEST_SERVER_HOSTNAME'),
            "username" => env('TEST_SERVER_USERNAME'),
            "password" => env('TEST_SERVER_PASSWORD'),
            "ip" => env('TEST_SERVER_IP'),
        ]);

        $serverManager->create_website(
            [
                
            ],
            [

            ],
        )
    }
}
