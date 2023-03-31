<?php

namespace App\Components;

use App\Entity\Content;
use App\Entity\Profile;
use App\Enum\SearchTypeEnum;
use Meilisearch\Bundle\SearchService;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsLiveComponent('global_search')]
final class GlobalSearchComponent
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public string $query = '';

    public function __construct(private readonly SearchService $searchService, private readonly TranslatorInterface $translator)
    {
    }

    #[ExposeInTemplate]
    public function getSearchResult(): ?array
    {
        if (empty($this->query)) {
            return null;
        }

        $results = [];

        $profiles = $this->searchService->rawSearch(Profile::class, $this->query, [
            ...SearchTypeEnum::getSearchOptions(SearchTypeEnum::Profiles),
            'highlightPreTag' => '<em class="bg-secondary-400">',
            'limit' => 5,
        ]);

        if (!empty($profiles['hits'])) {
            $results['profiles'] = [
                'results' => $profiles,
                'nameProperty' => 'username',
                'descProperty' => 'description',
                'slugProperty' => ['slug' => 'slug'],
                'route' => 'app_profile_show',
                'routeParam' => ['slug'],
            ];
        }

        $contents = $this->searchService->rawSearch(Content::class, $this->query, [
            ...SearchTypeEnum::getSearchOptions(SearchTypeEnum::Contents),
            'highlightPreTag' => '<em class="bg-secondary-400">',
            'limit' => 5,
        ]);

        if (!empty($contents['hits'])) {
            $results['contents'] = [
                'results' => $contents,
                'nameProperty' => 'title',
                'descProperty' => 'type',
                'descCallback' => fn (string $type): string => $this->translator->trans('content.type.'.$type),
                'slugProperty' => ['slug' => 'slug'],
                'route' => 'app_content_show',
                'routeParam' => ['slug'],
            ];
        }

        return $results;
    }
}
