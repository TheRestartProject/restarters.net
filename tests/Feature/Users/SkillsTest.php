<?php

namespace Tests\Feature;

use App\Role;
use App\Skills;
use App\User;
use DB;
use Hash;
use Mockery;
use Tests\TestCase;

class SkillsTest extends TestCase
{
    public function testIndex() {
        $this->loginAsTestUser(Role::RESTARTER);

        $response = $this->get('/skills');
        $response->assertRedirect('/user/forbidden');

        $skill1 = Skills::create([
                                     'skill_name'  => 'UT1',
                                     'category' => 1,
                                     'description' => 'Planning',
                                 ]);

        $this->loginAsTestUser(Role::ADMINISTRATOR);
        $response = $this->get('/skills');
        $response->assertSee('UT1');
    }

    public function testCreate() {
        $this->loginAsTestUser(Role::RESTARTER);

        $response = $this->post('/skills/create');
        $response->assertRedirect('/user/forbidden');

        $this->loginAsTestUser(Role::ADMINISTRATOR);

        $response = $this->post('/skills/create', [
            'skill_name' => 'UT1',
            'skill_desc' => 'UT'
        ]);
        $this->assertTrue($response->isRedirection());
        $response->assertSessionHas('success');

        $response = $this->get('/skills');
        $response->assertSee('UT1');
    }

    public function testEdit() {
        $this->loginAsTestUser(Role::RESTARTER);

        $skill1 = Skills::create([
                                     'skill_name'  => 'UT1',
                                     'description' => 'Planning',
                                 ]);

        $response = $this->get('/skills/edit/' . $skill1->id);
        $response->assertRedirect('/user/forbidden');

        $response = $this->post('/skills/edit/' . $skill1->id);
        $response->assertRedirect('/user/forbidden');

        $this->loginAsTestUser(Role::ADMINISTRATOR);
        $response = $this->get('/skills/edit/' . $skill1->id);
        $response->assertSee('name="skill-name"', false);

        $response = $this->post('/skills/edit/' . $skill1->id, [
            'skill-name'  => 'UT2',
            'category' => 2,
            'description' => 'Chaos',

        ]);
        $response->assertSessionHas('success');

        $response = $this->get('/skills');
        $response->assertSee('UT2');
    }

    public function testDelete() {
        $this->loginAsTestUser(Role::RESTARTER);

        $skill1 = Skills::create([
                                     'skill_name'  => 'UT1',
                                     'category' => 1,
                                     'description' => 'Planning',
                                 ]);

        $response = $this->get('/skills/delete/' . $skill1->id);
        $response->assertRedirect('/user/forbidden');

        $this->loginAsTestUser(Role::ADMINISTRATOR);
        $response = $this->get('/skills/delete/' . $skill1->id);
        $response->assertSessionHas('success');
    }
}