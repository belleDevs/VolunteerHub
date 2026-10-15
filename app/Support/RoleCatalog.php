<?php

namespace App\Support;

class RoleCatalog
{
    /**
     * Event roles and the volunteer skills that fit each one.
     *
     * @return array<string, list<string>>
     */
    public static function roles(): array
    {
        return [
            'Registration' => ['Public Relations', 'Community Outreach'],
            'Documentation' => ['Public Relations'],
            'Guest Speaker' => ['Public Relations', 'Event Coordinating'],
            'Emcee' => ['Public Relations', 'Event Coordinating'],
            'Photography' => ['Public Relations'],
            'Usher' => ['Community Outreach', 'Event Coordinating'],
            'Program Host' => ['Event Coordinating', 'Public Relations'],
            'First Aid' => ['First Aid Responder'],
            'Crowd Control' => ['Disaster Response'],
            'Logistics' => ['Disaster Response', 'Event Coordinating'],
            'Setup and Cleanup' => ['Community Outreach'],
            'Food Service' => ['Community Outreach'],
            'Coastal Cleanup' => ['Community Outreach', 'Mangrove Planting'],
            'Planting' => ['Mangrove Planting', 'Community Outreach'],
        ];
    }

    /**
     * Skills that relate to a role name, including a role the organization typed in.
     *
     * @return list<string>
     */
    public static function relatedSkillNames(string $title): array
    {
        $title = trim($title);
        if ($title === '') {
            return [];
        }

        foreach (self::roles() as $role => $skills) {
            if (mb_strtolower($role) === mb_strtolower($title)) {
                return $skills;
            }
        }

        $haystack = mb_strtolower($title);
        $found = [];
        foreach (self::keywords() as $needle => $skills) {
            if (!str_contains($haystack, $needle)) {
                continue;
            }
            foreach ($skills as $skill) {
                $found[$skill] = true;
            }
        }

        return array_keys($found);
    }

    /**
     * @return array<string, list<string>>
     */
    private static function keywords(): array
    {
        return [
            'document' => ['Public Relations'],
            'photo' => ['Public Relations'],
            'speaker' => ['Public Relations', 'Event Coordinating'],
            'guest' => ['Public Relations', 'Event Coordinating'],
            'registr' => ['Public Relations', 'Community Outreach'],
            'reception' => ['Public Relations', 'Community Outreach'],
            'welcome' => ['Public Relations', 'Community Outreach'],
            'usher' => ['Community Outreach', 'Event Coordinating'],
            'host' => ['Event Coordinating', 'Public Relations'],
            'emcee' => ['Public Relations', 'Event Coordinating'],
            'presenter' => ['Public Relations', 'Event Coordinating'],
            'stage' => ['Event Coordinating', 'Public Relations'],
            'sound' => ['Event Coordinating'],
            'coordinat' => ['Event Coordinating'],
            'first aid' => ['First Aid Responder'],
            'medic' => ['First Aid Responder'],
            'crowd' => ['Disaster Response'],
            'logistic' => ['Disaster Response', 'Event Coordinating'],
            'disaster' => ['Disaster Response'],
            'safety' => ['Disaster Response', 'First Aid Responder'],
            'mangrove' => ['Mangrove Planting', 'Community Outreach'],
            'plant' => ['Mangrove Planting', 'Community Outreach'],
            'sapling' => ['Mangrove Planting', 'Community Outreach'],
            'cleanup' => ['Community Outreach'],
            'clean-up' => ['Community Outreach'],
            'clean up' => ['Community Outreach'],
            'clean' => ['Community Outreach'],
            'trash' => ['Community Outreach'],
            'food' => ['Community Outreach'],
            'outreach' => ['Community Outreach', 'Public Relations'],
            'public relation' => ['Public Relations'],
            'bench' => ['Coastal Bench Building'],
        ];
    }
}
