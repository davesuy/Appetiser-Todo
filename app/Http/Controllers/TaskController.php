<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\Tag;

/**
 * @OA\Info(title="Task Management API", version="1.0")
 */
class TaskController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/tasks",
     *     summary="Get list of tasks",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=422, description="Invalid input")
     * )
     */
    public function index(Request $request)
    {
        $userId = Auth::id();

        $sortField = $request->input('sort_field', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        $validSortFields = ['title', 'description', 'due_date', 'created_at', 'completed_at', 'priority'];
        if (!in_array($sortField, $validSortFields)) {
            return response()->json(['message' => 'Invalid sort field'], 422);
        }

        $query = Task::where('user_id', $userId);

        // Filtering by completed date range
        if ($request->has('completed_from') && $request->has('completed_to')) {
            $query->whereBetween('completed_at', [$request->input('completed_from'), $request->input('completed_to')]);
        }

        // Filtering by priority
        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        // Filtering by due date range
        if ($request->filled('due_from') && $request->filled('due_to')) {
            $query->whereBetween('due_date', [$request->input('due_from'), $request->input('due_to')]);
        }

        // Filtering by archived date range
        if ($request->has('archived_from') && $request->has('archived_to')) {
            $query->whereBetween('archived_at', [$request->input('archived_from'), $request->input('archived_to')]);
        }

        // Filtering by search query (title and description)
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%$search%")
                    ->orWhere('description', 'like', "%$search%");
            });
        }

        $tasks = $query->with('tags')->orderBy($sortField, $sortOrder)->paginate(10);

        return response()->json($tasks, 200);
    }

    /**
     * @OA\Get(
     *     path="/api/tasks/{id}",
     *     summary="Get a specific task",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Task not found")
     * )
     */
    public function show($id)
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json(['message' => 'Task not found'], 404);
        }

        if ($task->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return response()->json($task, 200);
    }

    /**
     * @OA\Post(
     *     path="/api/tasks",
     *     summary="Create a new task",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="due_date", type="string", format="date"),
     *             @OA\Property(property="priority", type="string"),
     *             @OA\Property(property="tags", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(response=201, description="Task created"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=422, description="Invalid input")
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'due_date' => 'nullable|date|after_or_equal:today',
            'priority' => 'nullable|in:Urgent,High,Normal,Low',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $task = Task::create([
            'title' => $request->title,
            'description' => $request->description,
            'due_date' => $request->due_date,
            'priority' => $request->priority,
            'order' => Task::where('user_id', Auth::id())->count() + 1,
            'user_id' => Auth::id(),
        ]);

        if ($request->hasFile('attachments')) {
            $files = $request->file('attachments');
            foreach ($files as $file) {
                if ($file->isValid()) {
                    $path = $file->store('attachments', 'public');
                    $task->attachments()->create(['path' => $path, 'task_id' => $task->id]);
                }
            }
        }

        if ($request->has('tags')) {
            $tags = explode(',', $request->input('tags'));
            $tagIds = collect($tags)->map(function ($tagName) {
                $tagName = trim($tagName);
                return Tag::firstOrCreate(['name' => $tagName])->id;
            });
            $task->tags()->sync($tagIds);
        }

        return response()->json($task, 200);
    }

    /**
     * @OA\Put(
     *     path="/api/tasks/{id}",
     *     summary="Update a task",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="due_date", type="string", format="date"),
     *             @OA\Property(property="priority", type="string"),
     *             @OA\Property(property="tags", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(response=200, description="Task updated"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Task not found"),
     *     @OA\Response(response=422, description="Invalid input")
     * )
     */
    public function update(Request $request, $id)
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json(['message' => 'Task not found'], 404);
        }

        if ($task->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'due_date' => 'nullable|date|after_or_equal:today',
            'priority' => 'nullable|in:Urgent,High,Normal,Low',
            'attachments.*' => 'nullable|file|mimes:svg,png,jpg,mp4,csv,txt,doc,docx|max:20480', // 20MB max
            'tags' => 'nullable|array',
            'tags.*' => 'nullable|string|distinct',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $task->update($request->except('attachments', 'tags'));

        if ($request->hasFile('attachments')) {
            $files = $request->file('attachments');
            if (is_array($files)) {
                for ($i = 0; $i < count($files); $i++) {
                    $file = $files[$i];
                    if ($file->isValid()) {
                        $path = $file->store('attachments', 'public');
                        $task->attachments()->create(['path' => $path, 'task_id' => $task->id]);
                    } else {
                        Log::warning('Invalid file upload attempt', ['file' => $file]);
                    }
                }
            } else {
                if ($files->isValid()) {
                    $path = $files->store('attachments', 'public');
                    $task->attachments()->create(['path' => $path, 'task_id' => $task->id]);
                } else {
                    Log::warning('Invalid file upload attempt', ['file' => $files]);
                }
            }
        }

        if ($request->has('tags')) {
            $tags = explode(',', $request->input('tags'));
            $tagIds = collect($tags)->map(function ($tagName) {
                $tagName = trim($tagName);
                return Tag::firstOrCreate(['name' => $tagName])->id;
            });
            $task->tags()->sync($tagIds);
        }

        $task->load('attachments', 'tags');

        // Generate URLs for the attachments
        $attachments = $task->attachments->map(function ($attachment) {
            return [
                'id' => $attachment->id,
                'url' => Storage::url($attachment->path),
            ];
        });

        return response()->json(['task' => $task, 'attachments' => $attachments], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/tasks/{id}",
     *     summary="Delete a task",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=204, description="Task deleted"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Task not found")
     * )
     */
    public function destroy($id)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $task = Task::find($id);

        if (!$task) {
            return response()->json(['message' => 'Task not found'], 404);
        }

        if ($task->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $task->delete();

        return response()->json(null, 204);
    }

    /**
     * @OA\Patch(
     *     path="/api/tasks/{id}/complete",
     *     summary="Mark a task as completed",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Task marked as completed"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Task not found")
     * )
     */
    public function markAsCompleted($id)
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json(['message' => 'Task not found'], 404);
        }

        if ($task->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $task->completed_at = now();
        $task->save();

        return response()->json($task, 200);
    }

    /**
     * @OA\Patch(
     *     path="/api/tasks/{id}/incomplete",
     *     summary="Mark a task as incomplete",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Task marked as incomplete"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Task not found")
     * )
     */
    public function markAsIncomplete($id)
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json(['message' => 'Task not found'], 404);
        }

        if ($task->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $task->completed_at = null;
        $task->save();

        return response()->json($task, 200);
    }

    /**
     * @OA\Patch(
     *     path="/api/tasks/{id}/archive",
     *     summary="Archive a task",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Task archived"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Task not found")
     * )
     */
    public function archive($id)
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json(['message' => 'Task not found'], 404);
        }

        if ($task->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $task->archived_at = now();
        $task->save();

        return response()->json($task, 200);
    }

    /**
     * @OA\Patch(
     *     path="/api/tasks/{id}/restore",
     *     summary="Restore an archived task",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Task restored"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Task not found")
     * )
     */
    public function restore($id)
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json(['message' => 'Task not found'], 404);
        }

        if ($task->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $task->archived_at = null;
        $task->save();

        return response()->json($task, 200);
    }
}
