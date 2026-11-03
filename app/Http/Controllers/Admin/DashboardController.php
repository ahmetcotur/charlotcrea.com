<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Destination;
use App\Models\Film;
use App\Models\Inquiry;
use App\Models\Page;
use App\Models\Post;
use App\Models\Project;
use App\Models\Quote;
use App\Models\RevisionRequest;
use App\Models\Work;
use App\Services\Analytics\VisitReport;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, VisitReport $report): Response
    {
        $content = collect([
            ['label' => 'Sayfalar', 'model' => Page::class, 'route' => 'admin.page-manager'],
            ['label' => 'Projeler', 'model' => Project::class, 'route' => 'admin.projects.index'],
            ['label' => 'Filmler', 'model' => Film::class, 'route' => 'admin.films.index'],
            ['label' => 'Destinasyonlar', 'model' => Destination::class, 'route' => 'admin.destinations.index'],
            ['label' => 'Blog yazıları', 'model' => Post::class, 'route' => 'admin.posts.index'],
        ])->map(function ($item) {
            $item['total'] = $item['model']::count();
            $item['published'] = $item['model']::where('published', true)->count();
            $item['missingSeo'] = $item['model']::where('published', true)->whereHas('translations', fn ($query) => $query->where('active', true)->where(function ($query) {
                $query->whereNull('seo_title')->orWhere('seo_title', '')->orWhereNull('description')->orWhere('description', '');
            }))->count();

            return $item;
        });

        return response()->view('admin.dashboard', [
            'analytics' => $report->data($request),
            'content' => $content,
            'upcomingShoots' => Work::with('customer')->whereIn('status', ['planned', 'in_progress', 'on_hold'])->whereBetween('starts_at', [today(), today()->addDays(30)])->orderBy('starts_at')->take(5)->get(),
            'overdueCount' => Work::whereIn('status', ['planned', 'in_progress', 'on_hold'])->whereDate('due_at', '<', today())->count(),
            'acceptedCount' => Quote::where('status', 'accepted')->count(),
            'pipeline' => Quote::where('status', 'sent')->where(fn ($query) => $query->whereNull('valid_until')->orWhereDate('valid_until', '>=', today()))->select('currency')->selectRaw('SUM(amount) as total')->groupBy('currency')->get(),
            'counts' => [
                'inquiries' => Inquiry::where('status', 'new')->count(),
                'customers' => Customer::count(),
                'quotes' => Quote::whereIn('status', ['draft', 'sent'])->count(),
                'works' => Work::whereIn('status', ['planned', 'in_progress', 'on_hold'])->count(),
                'revisions' => RevisionRequest::whereIn('status', ['requested', 'in_review'])->count(),
            ],
            'inquiries' => Inquiry::latest('received_at')->take(5)->get(),
            'works' => Work::with('customer')->whereIn('status', ['planned', 'in_progress', 'on_hold'])->orderByRaw('due_at is null')->orderBy('due_at')->take(5)->get(),
        ])->header('Cache-Control', 'private, no-store');
    }
}
