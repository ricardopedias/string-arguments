<?php
namespace StringArgs;

class Expression
{
    /** @var array */
    protected $arguments = [];

    /** @var array */
    protected $default_arguments = [];

    /** @var array */
    protected $append_arguments = [];

    /**
     * Informação para debugar o tipo de parâmetros passados;
     * @var string none|json|array|inline
     */
    protected $source_expression = null;

    /**
     * Seta os nomes de parâmetros para padronizar entradas inline.
     * Quando uma expressão inline é utilizada, os parâmetros
     * não possuem nomes, mas apenas valores, sendo indexados numericamente.
     * O método setDefaultArgs especifica os nomes que serão usados
     * para substiyuir os índices numéricos.
     *
     * O argumento $params deve ser um array simples
     * Ex: ['route', 'id', 'permissions']
     *
     * @param array $arguments_names
     * @return StringArgs\Expression
     */
    public function setDefaultArgs(array $arguments_names)
    {
        $this->default_arguments = $arguments_names;
        return $this;
    }

    /**
     * Por padrão, quando o método addArgument é invocado, ele sobrescreve o
     * argumento anterior de mesmo nome. Os valores de $arguments_names servem
     * para mudar este comportamento.
     * Caso o nome passado se encontre em $arguments_names,
     * o argumento setado não sobrescreverá o anterior, mas será
     * somado ao final dele.
     *
     * O argumento $params deve ser um array simples
     * Ex: ['class', 'ul_class', 'div_class']
     *
     * @param array $params
     * @return StringArgs\Expression
     */
    public function setAppendArgs(array $arguments_names)
    {
        $this->append_arguments = array_flip(array_unique($arguments_names));
        return $this;
    }

    /**
     * Adiciona um argumento para disponibilizar na lista de argumentos.
     * O retorno é um array contendo duas chaves, onde os indices 'param' e 'value'
     * contém, respectivamente os parâmetros já tratados do método addArgument().
     * Se $force_override for setado como true, o comportamento de setAppendArgs
     * será ignorado, sobrescrevendo o valor do argumento.
     * @see StringArgs\Expression::setAppendArgs
     *
     * @param string  $param Nome da variável disponibilizada na view
     * @param mixed $value Valor qualquer
     * @param bool $force_override
     * @return array
     */
    public function addArgument(string $param, $value = true, $force_override = false)
    {
        $value = trim($value);      // remove espaços fora
        $value = trim($value, "'"); // remove aspas simples
        $value = trim($value, '"'); // remove aspas duplas
        $value = trim($value);      // remove espaços dentro das aspas

        if(is_numeric($param) && isset($this->default_arguments[$param])) {
            // Se for indice numérico e extistir um substituto
            $param = $this->default_arguments[$param];
        }

        if ($force_override == false
         && isset($this->append_arguments[$param])
         && isset($this->arguments[$param])
        ) {
             // é um parâmetro especial anexável
             $mixed = trim($this->arguments[$param], '"');
             $mixed .= " " . $value;
             $value = $mixed;
        }

        $this->arguments[$param] = $value;

        return [
            'param' => $param,
            'value' => $value
        ];
    }

    /**
     * Verifica se o argumento especificado existe.
     *
     * @return boolean
     */
    public function hasArgument($name)
    {
        return isset($this->arguments[$name]);
    }

    /**
     * Devolve todos o argumento especificado.
     *
     * @return mixed|null
     */
    public function getArgument($name)
    {
        return $this->arguments[$name] ?? null;
    }

    /**
     * Devolve todos os argumentos disponíveis no widget.
     *
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Interpreta os argumentos passados em forma de string.
     * Argumentos podem ser passados de três formas:
     * 1. Inline: " 'argumento1', 'argumento2' "
     * 2. Json: " { "argumento1" : 'valor', "argumento2" : 'valor' } "
     * 3. Array: " [ "argumento1" => 'valor', "argumento2" => 'valor' ] "
     *
     * @param  string $expression
     * @return array
     */
    public function parse(string $expression)
    {
        if (empty($expression)) {
            $this->source_expression = 'none';
            return [];
        }

        $expression = preg_replace('#\n#','', $expression);

        if(preg_match('#^\{#', $expression) === 1) {
            // Json
            $this->source_expression = 'json';
            $arguments = $this->parseJsonArguments($expression);

        } elseif(preg_match('#^\[#', $expression) === 1) {
            // Array
            $this->source_expression = 'array';
            $arguments = $this->parseArrayArguments($expression);

        } else {
            // Inline
            $this->source_expression = 'inline';
            $arguments = $this->parseInlineArguments($expression);
        }

        foreach ($arguments as $param => $value) {
            $this->addArgument($param, $value);
        }

        return $this->getArguments();
    }

    protected function parseJsonArguments($expression)
    {
        // Prepara o json
        $expression = preg_replace('#\{\s*#','{', $expression);  // transoforma [ em { sem espaços
        $expression = preg_replace('#\s*\}#','}', $expression);  // transoforma ] em } sem espaços
        $expression = preg_replace('#\s*:#',':', $expression);    // remove espaçoes antes de :
        $expression = preg_replace('#:\s*#',':', $expression);    // remove espaçoes após de :
        $expression = preg_replace('#,\s*#',',', $expression);    // remove espaços antes das virgulas
        $expression = preg_replace('#\s*,#',',', $expression);    // remove espaços após as virgulas

        // Aspas
        $expression = preg_replace("#'([a-zA-Z0-9\_-]*)':#",'"$1":', $expression); // transforma 'xxx': em "xxx":
        $expression = preg_replace("#:'([a-zA-Z0-9\_-]*)'#",':"$1"', $expression); // transforma :'xxx' em :"xxx":
        $expression = preg_replace('#:([^"])#',':"$1', $expression);  // Adiciona aspas ausentes no inicio dos valores
        $expression = preg_replace('#([^"]),"#','$1","', $expression);// Adiciona aspas ausentes no final dos valores
        $expression = preg_replace('#([^"])}#','$1"}', $expression);  // Adiciona aspas ausentes no final do json

        $decoded = json_decode($expression, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid json syntax');
        }

        return $decoded;
    }

    protected function parseArrayArguments($expression)
    {
        // Transforma o array em json
        $expression = preg_replace('#\[\s*?#','{', $expression);  // transoforma [ em { sem espaços
        $expression = preg_replace('#\s*?\]#','}', $expression);  // transoforma ] em } sem espaços
        $expression = preg_replace('#=>#',':', $expression);      // transoforma => em :
        $expression = preg_replace('#\s*:#',':', $expression);    // remove espaçoes antes de :
        $expression = preg_replace('#:\s*#',':', $expression);    // remove espaçoes após de :
        $expression = preg_replace('#,\s*#',',', $expression);    // remove espaços antes das virgulas
        $expression = preg_replace('#\s*,#',',', $expression);    // remove espaços após as virgulas

        // Aspas
        $expression = preg_replace("#'([a-zA-Z0-9\_-]*)':#",'"$1":', $expression); // transforma 'xxx': em "xxx":
        $expression = preg_replace("#:'([a-zA-Z0-9\_-]*)'#",':"$1"', $expression); // transforma :'xxx' em :"xxx":
        $expression = preg_replace('#:([^"])#',':"$1', $expression);  // Adiciona aspas ausentes no inicio dos valores
        $expression = preg_replace('#([^"]),"#','$1","', $expression);// Adiciona aspas ausentes no final dos valores
        $expression = preg_replace('#([^"])}#','$1"}', $expression);  // Adiciona aspas ausentes no final do json

        $decoded = json_decode($expression, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid array syntax');
        }

        return $decoded;
    }

    protected function parseInlineArguments($expression)
    {
        $splited = explode(',', $expression);
        $arguments = [];
        $function_part = false;
        $current = 0;
        foreach ($splited as $part){

            if(!isset($arguments[$current])) {
                $arguments[$current] = '';
            }

            // Função: string seguida de (
            if(preg_match('#[a-zA-Z] ?\(#', $part) && strpos($part, ')', -1) === false) {
                $function_part = true;
                $arguments[$current] = trim($part);
                continue;

            } elseif($function_part == true && strpos($part, ')', -1) !== false) {
                // Função: string terminando com )
                $function_part = false;
                $arguments[$current] .= "," . trim($part);

            } elseif($function_part == true) {
                // Função: argumentos internos
                $arguments[$current] .= "," . trim($part);
                continue;

            } else {
                $part = trim($part);
                $part = trim($part, '"');
                $arguments[$current] = trim($part, "'");
            }
            $current++;
        }

        return $arguments;
    }

    /**
     * Informação para debugar o tipo de parâmetros passados.
     * Se nenhuma expressão tiver sido analizada, o valor de
     * retorno será null.
     * Se existir uma análize de expressão, uma string será devolvida
     * com as seguintes possibilidades: none, json, array ou inline
     *
     * @return string|null
     */
    public function getSourceExpression()
    {
        return $this->source_expression;
    }
}
