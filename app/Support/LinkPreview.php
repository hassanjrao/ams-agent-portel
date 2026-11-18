<?php

namespace App\Support;

/**
 * What a shared portal link shows in WhatsApp, Facebook and the like. Those crawlers don't run
 * JavaScript, so the root view writes these into the page as Open Graph tags.
 */
class LinkPreview
{
    public const IMAGE = '/images/og-portal.jpg';

    // Bump when the image changes, so apps that cached the old one fetch it again.
    public const IMAGE_VERSION = 1;

    private const DEFAULT = [
        'title' => 'AMS Global Agent Portal',
        'description' => 'Umrah and group airline seats from AMS Global Travel & Tours, at agent fares. Travel agencies hold seats instantly, pay by bank transfer and get tickets and their statement online.',
    ];

    /**
     * Pages agents are sent links to, keyed by Inertia component.
     */
    private const PAGES = [
        'auth/Register' => [
            'title' => 'Register your travel agency | AMS Global Agent Portal',
            'description' => 'Travel agents: register your agency to book Umrah and group airline seats from AMS Global Travel & Tours at agent fares. Hold seats instantly, pay by bank transfer, get tickets and your statement online.',
        ],
        'auth/Login' => [
            'title' => 'Agent login | AMS Global Agent Portal',
            'description' => 'Sign in to the AMS Global Travel & Tours agent portal to hold group seats, send payments and manage your bookings.',
        ],
    ];

    /**
     * @return array{title: string, description: string, image: string, url: string}
     */
    public static function for(?string $component, string $url): array
    {
        $base = rtrim((string) config('app.url'), '/');

        return [
            ...(self::PAGES[$component] ?? self::DEFAULT),
            'image' => $base.self::IMAGE.'?v='.self::IMAGE_VERSION,
            'url' => $url,
        ];
    }
}
