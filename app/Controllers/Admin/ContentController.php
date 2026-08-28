<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Validator;

/** Banners, pages, programme, FAQs and upcoming events all live here. */
final class ContentController extends AdminController
{
    public function index(): string
    {
        return $this->render('admin.content', 'Content', [
            'banners'   => Database::select('SELECT * FROM banners ORDER BY position, sort_order, id'),
            'pages'     => Database::select('SELECT * FROM pages ORDER BY is_legal, title'),
            'programme' => Database::select('SELECT * FROM programme_items ORDER BY day_date, start_time'),
            'faqs'      => Database::select('SELECT * FROM faqs ORDER BY category, sort_order, id'),
            'events'    => Database::select('SELECT * FROM events ORDER BY starts_at'),
            'tab'       => (string) $this->request->input('tab', 'banners'),
        ]);
    }

    /* ------------------------------------------------------------ banners */

    public function saveBanner(): never
    {
        $validator = Validator::make($this->request->all(), [
            'title'    => 'required|max:190',
            'position' => 'required|max:60',
        ]);

        if ($validator->fails()) {
            $this->withErrors($validator->errors());
        }

        $id   = $this->request->int('id', 0);
        $data = [
            'position'        => (string) $this->request->input('position'),
            'title'           => (string) $this->request->input('title'),
            'subtitle'        => (string) $this->request->input('subtitle', ''),
            'body'            => (string) $this->request->input('body', ''),
            'image'           => (string) $this->request->input('image', ''),
            'image_alt'       => (string) $this->request->input('image_alt', ''),
            'cta_label'       => (string) $this->request->input('cta_label', ''),
            'cta_url'         => (string) $this->request->input('cta_url', ''),
            'secondary_label' => (string) $this->request->input('secondary_label', ''),
            'secondary_url'   => (string) $this->request->input('secondary_url', ''),
            'sort_order'      => $this->request->int('sort_order', 0),
            'is_active'       => $this->request->bool('is_active') ? 1 : 0,
        ];

        $image = $this->storeImage('image_file', 'banners');
        if ($image !== null) {
            $data['image'] = $image;
        }

        if ($id > 0) {
            Database::update('banners', $data, 'id = :id', ['id' => $id]);
            $this->flashSuccess('Banner saved.');
        } else {
            $id = Database::insert('banners', $data);
            $this->flashSuccess('Banner added.');
        }

        $this->audit('saved a banner', 'banner', $id);
        $this->back(url('/admin/content?tab=banners'));
    }

    public function deleteBanner(string $id): never
    {
        Database::delete('banners', 'id = ?', [(int) $id]);
        $this->audit('deleted a banner', 'banner', (int) $id);
        $this->flashSuccess('Banner deleted.');
        $this->back(url('/admin/content?tab=banners'));
    }

    /* -------------------------------------------------------------- pages */

    public function savePage(): never
    {
        $validator = Validator::make($this->request->all(), [
            'id'    => 'required|integer',
            'title' => 'required|max:190',
        ]);

        if ($validator->fails()) {
            $this->withErrors($validator->errors());
        }

        $id   = $this->request->int('id');
        $data = [
            'title'            => (string) $this->request->input('title'),
            'subtitle'         => (string) $this->request->input('subtitle', ''),
            'body_html'        => (string) $this->request->raw('body_html', ''),
            'meta_title'       => (string) $this->request->input('meta_title', ''),
            'meta_description' => (string) $this->request->input('meta_description', ''),
            'is_published'     => $this->request->bool('is_published') ? 1 : 0,
        ];

        $image = $this->storeImage('hero_image_file', 'pages');
        if ($image !== null) {
            $data['hero_image'] = $image;
        }

        Database::update('pages', $data, 'id = :id', ['id' => $id]);

        $this->audit('edited a page', 'page', $id, ['title' => $data['title']]);
        $this->flashSuccess('Page saved.');
        $this->back(url('/admin/content?tab=pages'));
    }

    /* ---------------------------------------------------------- programme */

    public function saveProgramme(): never
    {
        $validator = Validator::make($this->request->all(), [
            'day_date'   => 'required|date',
            'start_time' => 'required',
            'title'      => 'required|max:190',
        ]);

        if ($validator->fails()) {
            $this->withErrors($validator->errors());
        }

        $id   = $this->request->int('id', 0);
        $data = [
            'day_date'     => date('Y-m-d', (int) strtotime((string) $this->request->input('day_date'))),
            'start_time'   => date('H:i:s', (int) strtotime((string) $this->request->input('start_time'))),
            'end_time'     => ($end = (string) $this->request->input('end_time', '')) !== '' ? date('H:i:s', (int) strtotime($end)) : null,
            'title'        => (string) $this->request->input('title'),
            'description'  => (string) $this->request->input('description', ''),
            'location'     => (string) $this->request->input('location', ''),
            'track'        => (string) $this->request->input('track', ''),
            'is_highlight' => $this->request->bool('is_highlight') ? 1 : 0,
            'sort_order'   => $this->request->int('sort_order', 0),
            'is_active'    => $this->request->bool('is_active') ? 1 : 0,
        ];

        if ($id > 0) {
            Database::update('programme_items', $data, 'id = :id', ['id' => $id]);
            $this->flashSuccess('Programme item saved.');
        } else {
            $id = Database::insert('programme_items', $data);
            $this->flashSuccess('Programme item added.');
        }

        $this->audit('saved a programme item', 'programme_item', $id);
        $this->back(url('/admin/content?tab=programme'));
    }

    public function deleteProgramme(string $id): never
    {
        Database::delete('programme_items', 'id = ?', [(int) $id]);
        $this->audit('deleted a programme item', 'programme_item', (int) $id);
        $this->flashSuccess('Programme item deleted.');
        $this->back(url('/admin/content?tab=programme'));
    }

    /* --------------------------------------------------------------- FAQs */

    public function saveFaq(): never
    {
        $validator = Validator::make($this->request->all(), [
            'question' => 'required|max:255',
            'answer'   => 'required',
        ]);

        if ($validator->fails()) {
            $this->withErrors($validator->errors());
        }

        $id   = $this->request->int('id', 0);
        $data = [
            'category'   => (string) ($this->request->input('category') ?: 'General'),
            'question'   => (string) $this->request->input('question'),
            'answer'     => (string) $this->request->raw('answer'),
            'sort_order' => $this->request->int('sort_order', 0),
            'is_active'  => $this->request->bool('is_active') ? 1 : 0,
        ];

        if ($id > 0) {
            Database::update('faqs', $data, 'id = :id', ['id' => $id]);
            $this->flashSuccess('Question saved.');
        } else {
            $id = Database::insert('faqs', $data);
            $this->flashSuccess('Question added.');
        }

        $this->audit('saved an FAQ', 'faq', $id);
        $this->back(url('/admin/content?tab=faqs'));
    }

    public function deleteFaq(string $id): never
    {
        Database::delete('faqs', 'id = ?', [(int) $id]);
        $this->audit('deleted an FAQ', 'faq', (int) $id);
        $this->flashSuccess('Question deleted.');
        $this->back(url('/admin/content?tab=faqs'));
    }

    /* ------------------------------------------------------------- events */

    public function saveEvent(): never
    {
        $validator = Validator::make($this->request->all(), [
            'title'     => 'required|max:190',
            'starts_at' => 'required|date',
        ]);

        if ($validator->fails()) {
            $this->withErrors($validator->errors());
        }

        $id   = $this->request->int('id', 0);
        $data = [
            'title'       => (string) $this->request->input('title'),
            'slug'        => slugify((string) ($this->request->input('slug') ?: $this->request->input('title'))),
            'description' => (string) $this->request->raw('description', ''),
            'starts_at'   => date('Y-m-d H:i:s', (int) strtotime((string) $this->request->input('starts_at'))),
            'ends_at'     => ($end = (string) $this->request->input('ends_at', '')) !== '' ? date('Y-m-d H:i:s', (int) strtotime($end)) : null,
            'location'    => (string) $this->request->input('location', ''),
            'link_url'    => (string) $this->request->input('link_url', ''),
            'is_active'   => $this->request->bool('is_active') ? 1 : 0,
        ];

        $image = $this->storeImage('image_file', 'events');
        if ($image !== null) {
            $data['image'] = $image;
        }

        if ($id > 0) {
            Database::update('events', $data, 'id = :id', ['id' => $id]);
            $this->flashSuccess('Event saved.');
        } else {
            if ((int) Database::scalar('SELECT COUNT(*) FROM events WHERE slug = ?', [$data['slug']]) > 0) {
                $data['slug'] .= '-' . bin2hex(random_bytes(2));
            }

            $id = Database::insert('events', $data);
            $this->flashSuccess('Event added.');
        }

        $this->audit('saved an event', 'event', $id);
        $this->back(url('/admin/content?tab=events'));
    }

    public function deleteEvent(string $id): never
    {
        Database::delete('events', 'id = ?', [(int) $id]);
        $this->audit('deleted an event', 'event', (int) $id);
        $this->flashSuccess('Event deleted.');
        $this->back(url('/admin/content?tab=events'));
    }
}
