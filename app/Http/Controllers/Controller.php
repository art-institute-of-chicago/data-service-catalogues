<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Manager;

use App\Http\Transformers\ApiSerializer;

use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{

    protected $model;

    protected $transformer;

    private $fractal;

    public function __construct(Manager $fractal)
    {
        $this->fractal = $fractal;
        // $fractal->setSerializer(new ApiSerializer);
    }

    const LIMIT_MAX = 1000;


    /**
     * Display the specified resource.
     *
     * @param null $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {

        // Technically this will never be called, b/c we only bind Route.get
        if ($request->method() != 'GET')
        {
            return $this->respondMethodNotAllowed();
        }

        // Only allow numeric ids
        if (intval($id) <= 0)
        {
            return $this->respondInvalidSyntax();
        }

        $item = $this->find($id);

        if (!$item)
        {
            return $this->respondNotFound();
        }

        return $this->item($item, new $this->transformer);

    }


    /**
     * Call to find models. Override this method when logic to retieve models
     * is more complex than a simple `$model->find($id)` call.
     */
    public function find($ids)
    {

        return ($this->model)::find($ids);

    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        // Technically this will never be called, b/c we only bind Route.get
        if ($request->method() != 'GET')
        {
            return $this->respondMethodNotAllowed();
        }

        // Process ?ids= query param
        $ids = $request->input('ids');

        if ($ids)
        {
            return $this->showMutliple($ids);
        }

        // Check if the ?limit= is too big
        $limit = $request->input('limit') ?: 12;

        if ($limit > static::LIMIT_MAX)
        {
            return $this->respondBigLimit();
        }

        // Assumes the inheriting class set model and transformer
        $all = ($this->model)::paginate($limit);

        return $this->collection($all, new $this->transformer);

    }


    /**
     * Display multiple resources.
     *
     * @param string $ids
     * @return \Illuminate\Http\Response
     */
    private function showMutliple($ids = '')
    {

        $ids = explode(',',$ids);

        if (count($ids) > static::LIMIT_MAX)
        {
            return $this->respondTooManyIds();
        }

        $all = $this->find($ids);

        return $this->collection($all, new $this->transformer);

    }


    private function collection($all, $transformer)
    {

        $collection = new Collection($all, $transformer);

        $data = $this->fractal->createData($collection)->toArray();

        if ($all instanceof LengthAwarePaginator)
        {

            $paginator = [
                'total' => $all->total(),
                'limit' => (int) $all->perPage(),
                'offset' => (int) $all->perPage() * ( $all->currentPage() - 1 ),
                'total_pages' => $all->lastPage(),
                'current_page' => $all->currentPage(),
            ];

            if ($all->previousPageUrl()) {
                $paginator['prev_url'] = $all->previousPageUrl() .'&limit=' .$all->perPage();
            }
            if ($all->hasMorePages()) {
                $paginator['next_url'] = $all->nextPageUrl() .'&limit=' .$all->perPage();
            }

            $data = array_merge(['pagination' => $paginator], $data);

        }

        return $this->respond($data);

    }


    private function item($event, $transformer)
    {

        $item = new Item($event, $transformer);

        $data = $this->fractal->createData($item)->toArray();

        return $this->respond($data);

    }


    /**
     * Helper method to return successful JSON response.
     * Use this instead of Laravel's response() helper.
     *
     * @param array $data
     * @param array $headers (optional)
     *
     * @return \Illuminate\Http\Response
     */
    private function respond($data, $headers = [])
    {
        return response()->json($data, Response::HTTP_OK, $headers);
    }


    /**
     * Helper method for returning errors in JSON.
     *
     * @param string $message
     * @param string $detail
     * @param int $status (optional)
     *
     * @return \Illuminate\Http\Response
     */
    private function error($message, $detail, $status = 500)
    {

        return response()->json([
            'status' => $status,
            'error' => $message,
            'detail' => $detail,
        ], $status);

    }

    // TODO: These should likely be moved to Exceptions
    // https://stackoverflow.com/questions/28944097/laravel-5-handle-exceptions-when-request-wants-json
    private function respondNotFound($message = 'Not found', $detail = 'The item you requested cannot be found.')
    {
        return $this->error($message, $detail, Response::HTTP_NOT_FOUND);
    }

    private function respondInvalidSyntax($message = 'Invalid syntax', $detail = 'The identifier is invalid.')
    {
        return $this->error($message, $detail, Response::HTTP_BAD_REQUEST);
    }

    private function respondFailure($message = 'Failed request', $detail = 'The request failed.')
    {
        return $this->error($message, $detail, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    private function respondForbidden($message = 'Forbidden', $detail = 'This request is forbidden.')
    {
        return $this->error($message, $detail, Response::HTTP_FORBIDDEN);
    }

    private function respondTooManyIds($message = 'Invalid number of ids', $detail = 'You have requested too many ids. Please send a smaller amount.')
    {
        return $this->error($message, $detail, Response::HTTP_FORBIDDEN);
    }

    private function respondBigLimit($message = 'Invalid limit', $detail = 'You have requested too many resources. Please set a smaller limit.')
    {
        return $this->error($message, $detail, Response::HTTP_FORBIDDEN);
    }

    private function respondMethodNotAllowed($message = 'Method not allowed', $detail = 'Method not allowed.')
    {
        return $this->error($message, $detail, Response::HTTP_METHOD_NOT_ALLOWED);
    }

}
