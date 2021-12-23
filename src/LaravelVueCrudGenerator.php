<?php

namespace Arturssmirnovs\LaravelVueCrudGenerator;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class LaravelVueCrudGenerator extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vue:generate-crud {model : The ID of the user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * @var Collection
     */
    protected $fields = [];

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Create a new controller creator command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    public function getSingular()
    {
        return Str::singular($this->argument('model'));
    }

    public function getPlural()
    {
        return Str::plural($this->argument('model'));
    }

    public function getSnake()
    {
        return Str::snake($this->getPlural());
    }

    public function hasTable() {
        return Schema::hasTable($this->getSnake());
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (!$this->hasTable()) {
            return $this->error("Table don't exists");
        }

        $this->fields = new Collection();

        $data = DB::select('DESCRIBE "'.$this->getSnake().'";');

        foreach ($data as $key)
        {
            $type = Str::match("#(.+)\(|.+#", $key->Type);
            $inputType = $this->_getInputType($type);
            $laravelType = $this->_getLaravelType($inputType);

            $this->fields->add([
                "name" => $key->Field,
                "type" => $type,
                "input_type" => $inputType,
                "laravel_type" => $laravelType,
                "required" => (($key->Null === 'NO') ? true : false),
                "max" => Str::match("#\d+#", $key->Type),
            ]);

        };

//        if ($this->isWritable())
//        {
            $this->makeModel();
            $this->makeController();
            $this->makeResource();
            $this->makeResourceCollection();
            $this->makeCreateRequest();
            $this->makeUpdateRequest();

            // Route::resource('posts', \App\Http\Controllers\PostController::class); write to api
//        }

        return 1;
    }

    private function isWritable()
    {
        if ($this->files->exists($this->getControllerPath()))
        {
            if (!$this->confirm($this->getControllerClass().'.php already exists. Would you like to overwrite this controller?'))
            {
                return false;
            }
        }

        if ($this->files->exists($this->getModelPath()))
        {
            if (!$this->confirm($this->getModelClass().'.php already exists. Would you like to overwrite this model?'))
            {
                return false;
            }
        }

        if ($this->files->exists($this->getResourcePath()))
        {
            if (!$this->confirm($this->getResourceClass().'.php already exists. Would you like to overwrite this resource?'))
            {
                return false;
            }
        }

        if ($this->files->exists($this->getResourceCollectionPath()))
        {
            if (!$this->confirm($this->getResourceCollectionClass().'.php already exists. Would you like to overwrite this collection?'))
            {
                return false;
            }
        }

        if ($this->files->exists($this->getCreateRequestPath()))
        {
            if (!$this->confirm($this->getCreateRequestClass().'.php already exists. Would you like to overwrite this request?'))
            {
                return false;
            }
        }

        if ($this->files->exists($this->getUpdateRequestPath()))
        {
            if (!$this->confirm($this->getUpdateRequestClass().'.php already exists. Would you like to overwrite this request?'))
            {
                return false;
            }
        }

        return true;
    }

    private function makeController()
    {
        $content = $this->replacements($this->files->get(__DIR__."/stub/controller.stub"));

        return $this->files->put($this->getControllerPath(), $content);
    }

    private function makeModel()
    {
        $content = $this->replacements($this->files->get(__DIR__."/stub/model.stub"));

        return $this->files->put($this->getModelPath(), $content);
    }

    private function makeResource()
    {
        $content = $this->replacements($this->files->get(__DIR__."/stub/resource.stub"));

        return $this->files->put($this->getResourcePath(), $content);
    }

    private function makeResourceCollection()
    {
        $content = $this->replacements($this->files->get(__DIR__."/stub/resource-collection.stub"));

        return $this->files->put($this->getResourceCollectionPath(), $content);
    }

    private function makeCreateRequest()
    {
        $content = $this->replacements($this->files->get(__DIR__."/stub/request-create.stub"));

        return $this->files->put($this->getCreateRequestPath(), $content);
    }

    private function makeUpdateRequest()
    {
        $content = $this->replacements($this->files->get(__DIR__."/stub/request-update.stub"));

        return $this->files->put($this->getUpdateRequestPath(), $content);
    }

    private function replacements($stub) {
        return Str::replace([
            "{{ rootNamespace }}",

            "{{ namespaceModel }}",
            "{{ classModel }}",
            "{{ variableModel }}",

            "{{ namespaceController }}",
            "{{ classController }}",

            "{{ namespaceResource }}",
            "{{ classResource }}",

            "{{ namespaceResourceCollection }}",
            "{{ classResourceCollection }}",

            "{{ namespaceCreateRequest }}",
            "{{ classCreateRequest }}",

            "{{ namespaceUpdateRequest }}",
            "{{ classUpdateRequest }}",

            "{{ fields }}"
        ], [
            "-", //  //

            $this->getModelNamespace(),
            $this->getModelClass(),
            $this->getModelVariable(),

            $this->getControllerNamespace(),
            $this->getControllerClass(),

            $this->getResourceNamespace(),
            $this->getResourceClass(),

            $this->getResourceNamespace(),
            $this->getResourceCollectionClass(),

            $this->getRequestNamespace(),
            $this->getCreateRequestClass(),

            $this->getRequestNamespace(),
            $this->getUpdateRequestClass(),

            $this->getFields()
         ], $stub);
    }

    public function getBasePath()
    {
        return base_path('');
    }

    public function getModelClass()
    {
        return $this->getSingular();
    }

    public function getModelVariable()
    {
        return "\$".lcfirst($this->getSingular());
    }

    public function getControllerPath()
    {
        return $this->getBasePath()."/".$this->getControllerNamespace()."/".$this->getControllerClass().".php";
    }

    public function getModelPath()
    {
        return $this->getBasePath()."/".$this->getModelNamespace()."/".$this->getModelClass().".php";
    }

    public function getResourcePath()
    {
        return $this->getBasePath()."/".$this->getResourceNamespace()."/".$this->getResourceClass().".php";
    }

    public function getResourceCollectionPath()
    {
        return $this->getBasePath()."/".$this->getResourceNamespace()."/".$this->getResourceCollectionClass().".php";
    }

    public function getCreateRequestPath()
    {
        return $this->getBasePath()."/".$this->getRequestNamespace()."/".$this->getCreateRequestClass().".php";
    }

    public function getUpdateRequestPath()
    {
        return $this->getBasePath()."/".$this->getRequestNamespace()."/".$this->getUpdateRequestClass().".php";
    }

    public function getControllerClass()
    {
        return $this->getSingular()."Controller";
    }

    public function getModelNamespace()
    {
        return "App\Models";
    }

    public function getControllerNamespace()
    {
        return "App\Http\Controllers";
    }

    public function getResourceNamespace()
    {
        return "App\Http\Resources";
    }

    public function getResourceClass()
    {
        return $this->getSingular()."Resource";
    }

    public function getResourceCollectionClass()
    {
        return $this->getSingular()."Collection";
    }

    public function getRequestNamespace()
    {
        return "App\Http\Requests";
    }

    public function getCreateRequestClass()
    {
        return "Store".$this->getSingular()."Request";
    }

    public function getUpdateRequestClass()
    {
        return "Update".$this->getSingular()."Request";
    }

    public function getFields()
    {
        $content = "[".PHP_EOL;

        foreach ($this->fields as $field)
        {
            $content .= "           '".$field["name"]."' => '".($field["required"] ? "required" : "sometimes")."|".$field["laravel_type"]."".($field["max"] ? "|max:".$field["max"] : "")."',".PHP_EOL;
        }

        $content .= "       ]";

        return $content;
    }

    /**
     * Get input field type from mysql db
     *
     * @param $type
     * @return string
     */
    private function _getInputType($type)
    {
        if ((new Collection(['char','varchar','tinytext','text','mediumtext','longtext','tinytext']))->contains($type))
        {
            return "text";
        }

        if ((new Collection(['tinyint','smallint','mediumint','int','bigint','decimal','float','double', 'year']))->contains($type))
        {
            return "number";
        }

        if ((new Collection(['text','mediumtext','longtext']))->contains($type))
        {
            return "textarea";
        }

        if ((new Collection(['timestamp', 'datetime']))->contains($type))
        {
            return "datetime";
        }

        if ((new Collection(['date']))->contains($type))
        {
            return "date";
        }

        if ((new Collection(['time']))->contains($type))
        {
            return "time";
        }

        if ((new Collection(['eum']))->contains($type))
        {
            return "dropdown";
        }

        return "text";
    }

    public function _getLaravelType($type)
    {
        if ($type == "text")
        {
            return "string";
        }
        if ($type == "number")
        {
            return "numeric";
        }
        if ($type == "textarea")
        {
            return "string";
        }
        if ($type == "datetime")
        {
            return "date";
        }
        if ($type == "date")
        {
            return "date";
        }
        if ($type == "time")
        {
            return "string";
        }
        if ($type == "dropdown")
        {
            return "string";
        }

        return "string";
    }

    /**
     * Get the root namespace for the class.
     *
     * @return string
     */
    protected function rootNamespace()
    {
        return $this->laravel->getNamespace();
    }
}