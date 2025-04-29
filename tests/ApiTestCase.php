<?php

namespace Tests;

use App\Models\Role;
use App\Models\User;

/**
 * Base test case for API tests
 * Automatically handles authentication for API endpoints
 */
class ApiTestCase extends TestCase
{
    protected $apiUser = null;

        
    /**
     * Default role used for API authentication
     */
    protected $defaultApiRole = Role::ADMINISTRATOR;
    
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup API authentication by default with the admin role
        $this->setupApiAuth($this->defaultApiRole);
    }
    
    /**
     * Setup API authentication for test
     * 
     * @param int $role The role to use for authentication (defaults to Administrator)
     * @return User The authenticated user
     */
    protected function setupApiAuth($role = null)
    {
        if ($role === null) {
            $role = Role::ADMINISTRATOR;
        }
        
        $this->apiUser = $this->fastLoginAsTestUser($role);
        
        return $this->apiUser;
    }
    
    /**
     * Get with API authentication
     */
    protected function getWithAuth($uri, array $headers = [])
    {
        if (!$this->apiUser) {
            $this->setupApiAuth();
        }
        
        $separator = strpos($uri, '?') !== false ? '&' : '?';
        $uri .= "{$separator}api_token={$this->apiUser->api_token}";
        
        return $this->get($uri, $headers);
    }
    
    /**
     * Post with API authentication
     */
    protected function postWithAuth($uri, array $data = [], array $headers = [])
    {
        if (!$this->apiUser) {
            $this->setupApiAuth();
        }
        
        $separator = strpos($uri, '?') !== false ? '&' : '?';
        $uri .= "{$separator}api_token={$this->apiUser->api_token}";
        
        return $this->post($uri, $data, $headers);
    }
    
    /**
     * Put with API authentication
     */
    protected function putWithAuth($uri, array $data = [], array $headers = [])
    {
        if (!$this->apiUser) {
            $this->setupApiAuth();
        }
        
        $separator = strpos($uri, '?') !== false ? '&' : '?';
        $uri .= "{$separator}api_token={$this->apiUser->api_token}";
        
        return $this->put($uri, $data, $headers);
    }
    
    /**
     * Patch with API authentication
     */
    protected function patchWithAuth($uri, array $data = [], array $headers = [])
    {
        if (!$this->apiUser) {
            $this->setupApiAuth();
        }
        
        $separator = strpos($uri, '?') !== false ? '&' : '?';
        $uri .= "{$separator}api_token={$this->apiUser->api_token}";
        
        return $this->patch($uri, $data, $headers);
    }
    
    /**
     * Delete with API authentication
     */
    protected function deleteWithAuth($uri, array $data = [], array $headers = [])
    {
        if (!$this->apiUser) {
            $this->setupApiAuth();
        }
        
        $separator = strpos($uri, '?') !== false ? '&' : '?';
        $uri .= "{$separator}api_token={$this->apiUser->api_token}";
        
        return $this->delete($uri, $data, $headers);
    }
} 

