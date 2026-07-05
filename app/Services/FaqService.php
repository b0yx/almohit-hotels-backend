<?php

namespace App\Services;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FaqService
{
    public static function syncFaqs(Model $parent, mixed $faqs): void
    {
        if ($faqs === null || ! is_array($faqs)) {
            return;
        }

        DB::transaction(function () use ($parent, $faqs) {
            $existingIds = $parent->faqs()->pluck('id')->all();
            $keptIds = [];

            foreach (array_values($faqs) as $index => $faqData) {
                if (! is_array($faqData)) {
                    continue;
                }

                $id = array_key_exists('id', $faqData) && $faqData['id'] !== null && $faqData['id'] !== '' ? (int) $faqData['id'] : null;
                $data = self::normalizeFaqData($faqData, $index);

                if ($id && in_array($id, $existingIds, true)) {
                    $faq = Faq::query()->find($id);
                    if ($faq && $faq->faqable_type === $parent->getMorphClass() && (int) $faq->faqable_id === (int) $parent->getKey()) {
                        $faq->update($data);
                        $keptIds[] = $id;
                    }
                } else {
                    $created = $parent->faqs()->create($data);
                    $keptIds[] = $created->id;
                }
            }

            $toDelete = array_diff($existingIds, $keptIds);
            if (! empty($toDelete)) {
                Faq::query()->whereIn('id', $toDelete)->delete();
            }
        });
    }

    private static function normalizeFaqData(array $faqData, int $index): array
    {
        $question = (string) ($faqData['question'] ?? '');
        $slug = (string) ($faqData['slug'] ?? '');

        return [
            'question' => $question,
            'answer' => (string) ($faqData['answer'] ?? ''),
            'question_ar' => self::nullableString($faqData['question_ar'] ?? null),
            'answer_ar' => self::nullableString($faqData['answer_ar'] ?? null),
            'slug' => $slug !== '' ? Str::slug($slug) : Str::slug($question),
            'meta_title' => self::nullableString($faqData['meta_title'] ?? null),
            'meta_description' => self::nullableString($faqData['meta_description'] ?? null),
            'meta_title_ar' => self::nullableString($faqData['meta_title_ar'] ?? null),
            'meta_description_ar' => self::nullableString($faqData['meta_description_ar'] ?? null),
            'canonical_url' => self::nullableString($faqData['canonical_url'] ?? null),
            'sort_order' => array_key_exists('sort_order', $faqData) && $faqData['sort_order'] !== null ? (int) $faqData['sort_order'] : ($index + 1),
            'is_active' => array_key_exists('is_active', $faqData) ? (bool) $faqData['is_active'] : true,
        ];
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
}
