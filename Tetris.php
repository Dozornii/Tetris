<?php
namespace Tetris;
header('Access-Control-Allow-Origin: *');
// ------------------------------------------------------------------------
//                   Tetris  Game
//                  By : Vladimir Younkin 16.02.2021
//-------------------------------------------------------------------------
interface Game
{
    public function draw();
    public function get();
    public function set($data);
}



class Tetromino implements Game
{
    private $min_x=-2;
    private $max_x=7;

    public $pos_x = 0;
    public $pos_y = 0;
    //private $canvas;

    private $frame_max = 30;
    public $type;
    private $map = [];
    private $is_freez = false;

    private static $shapes = [
        '⋆' => [[2,1],[2,2],[2,3],[2,4]],
        '★' => [[1,2],[1,3],[2,2],[2,3]],
        '✶' => [[1,2],[2,2],[3,2],[3,3]],
        '✷' => [[1,2],[1,3],[1,4],[2,3]],
        '✫' => [[1,2],[2,2],[1,3],[2,1]]
    ];

    public function __construct()
    {
        $this->reset();
    }

    


    private static function shapesList()
    {
        return array_keys(self::$shapes);
    }

    private static function randShape()
    {
        $shapesList = self::shapesList();
        $rand_idx = rand(0, count($shapesList) - 1);
        return $shapesList[$rand_idx];
    }
    
    public function tilesPoint()
    {
        foreach ($this->map as $y => &$row) {
            foreach ($row as $x => &$value) {
                if ($value) {
                    yield [$x, $y];
                }
            }
        }
    }

    public function tilesPosPoint()
    {
        foreach ($this->tilesPoint() as list($x, $y)) {
            $pos_point_x = $x + $this->pos_x;
            $pos_point_y = $y + $this->pos_y;
            yield [$pos_point_x, $pos_point_y];
        }
    }

    public function reset()
    {
        $this->type = self::randShape();

        
        $this->map = array_fill(0, 5, 0);
        foreach ($this->map as &$row) {
            $row = array_fill(0, 5, 0);
        }

    
        $tiles_start_point = self::$shapes[$this->type];
        foreach ($tiles_start_point as list($y, $x)) {
            $this->map[$y][$x] = 'x';
        }

        

        $this->pos_x = 3;
        $this->pos_y = -1;


     
        $this->is_freez = false;
    }
    
    public function move($direction)
    {


        switch ($direction) {
            case 'down':
                $this->pos_y += 1;
                break;

            case 'left':
                if($this->pos_x>$this->min_x)
                $this->pos_x -= 1;
                break;

            case 'right':
                if($this->pos_x<$this->max_x)
                $this->pos_x += 1;
                break;
        }


    }

    public function rotate()
    {
        if ($this->is_freez===true) {
            return;
        }
            
        $map = [];
        foreach ($this->map as $y => &$row) {
            foreach ($row as $x => &$value) {
                $r_y = $x;
                $r_x = 4 - $y;
                $map[$r_y][$r_x] = $value;
            }
        }

        
         $this->map = $map;
    }

    public function draw(){
   
            $this->move('down');
 

    }
    public function get()
    {
        $data_arr=array();
        $data_arr['pos_x'] = $this->pos_x;
        $data_arr['pos_y'] = $this->pos_y;
        $data_arr['frame_max'] = $this->frame_max ;
        $data_arr['type'] = $this->type;
        $data_arr['max'] = $this->map;
        $data_arr['is_freez'] = $this->is_freez;

        return $data_arr;
    }
    public function set($data_arr)
    {
        
        $this->pos_x = $data_arr['pos_x'];
        $this->pos_y = $data_arr['pos_y'];
        $this->frame_max = $data_arr['frame_max'];
        $this->type = $data_arr['type'] ;
        $this->map = $data_arr['max'];
        $this->is_freez = $data_arr['is_freez'];
    }

    public function freez()
    {
        $this->is_freez = true;
  
    }

    public function isFreez()
    {
        return $this->is_freez;
    }
}

class Board implements Game
{
    private $rows = 0;
    private $cols = 0;
    private $board;

    private function init()
    {
        $this->board = array_fill(0, $this->rows, 0);
        foreach ($this->board as &$row) {
            $row = array_fill(0, $this->cols, 0);
        }
    }

    public function __construct($rows, $cols)
    {
        $this->rows = $rows;
        $this->cols = $cols;
        $this->init();
    }

    public function validatePoint($x, $y)
    {
        return $x >= 0 && $x < $this->cols
            && $y >= 0 && $y < $this->rows;
    }

    public function draw(){
        $this->removeCompletedLine();
    }

    public function existTile($x, $y)
    {
        if (!$this->validatePoint($x, $y)) {
            return false;
        }

        return !($this->board[$y][$x]=='0');
    }

    public function addTile($x, $y, $tile_type)
    {
      
        if ($this->validatePoint($x, $y)) {
            $this->board[$y][$x] = $tile_type;
        }
    }


    private function removeCompletedLine()
    {
        $map = [];

        foreach ($this->board as $y => &$row) {
            $count = 0;
            $idx_bomb = -1;
            foreach ($row as $i => &$value) {
                if ($value) {
                    ++$count;
                }

                if ($value === 'X') {
                    $idx_bomb = $i;
                }
            }

            if ($count == $this->cols) {
                if ($idx_bomb < $this->cols - 1) {
                    $row[$idx_bomb + 1] = 'X';
                }
            }

            if ($idx_bomb == $this->cols - 1) {
                $row = array_fill(0, $this->cols, 0);
            } else {
                $board[] = $row;
            }
        }

        $row = array_fill(0, $this->cols, 0);
        $count = $this->rows - count($board);
        for ($i = 0; $i < $count; $i++) {
            array_unshift($board, $row);
        }

        $this->board = $board;
    }

    public function boardData()
    {
        return $this->board;
    }

    public function get()
    {
        $data_arr=array();
        $data_arr['rows'] = $this->rows;
        $data_arr['cols'] = $this->cols;
        $data_arr['board'] = $this->board;
        return $data_arr;
    }
    public function set($data_arr)
    {
        $this->rows = $data_arr['rows'];
        $this->cols = $data_arr['cols'];
        $this->board = $data_arr['board'];
    }
}

class Tetris 
{
    private $board = null;
    private $board_r = 30;
    private $board_c = 10;
    private $canvas=null;
    private $tetromino = null;
    

    public function  __construct()
    {
        $this->board = new Board($this->board_r, $this->board_c);
        $this->tetromino = new Tetromino();
    }

    public function screenDisplay()
    {
        $this->drawCanvas();


        $str = '';
        foreach ($this->canvas as &$row) {
            foreach ($row as &$col) {
                $str .=' '.$col;
            }
            $str .= "</br>";
        }
        $temp_arr=array();
        $temp_arr["canvas"]=$this->canvas;
        $temp_arr["tetromino"]=$this->tetromino->get();
        $temp_arr["board"]=$this->board->get();
        echo json_encode($temp_arr);
    }
    private function drawCanvas()
    {
        $this->canvas = $this->board->boardData();

        foreach ($this->tetromino->tilesPosPoint() as list($x, $y)) {
            if ($this->board->validatePoint($x, $y)) {
                $this->canvas[$y][$x] = $this->tetromino->type;
            }
        }

    }
    private function freezTetromino()
    {
       
        if ($this->tetromino->isFreez()===true) {
            return;
        }
        $this->tetromino->freez();
        
        foreach ($this->tetromino->tilesPosPoint() as list($x, $y)) {
            
            $this->board->addTile($x, $y, $this->tetromino->type);
        }
        
    }

    public function setTetromino($data){
        $this->tetromino->set($data);
    }
    public function setBoard($data){
        $this->board->set($data);
    }
    
    public function run()
    {
        $is_collision = $this->collisionCheck();
            if ($is_collision) {
                $temp_arr=array();
                $this->is_play = false;
                $temp_arr["canvas"]=$this->canvas;
                $temp_arr["tetromino"]=$this->tetromino->get();
                $temp_arr["board"]=$this->board->get();
                echo json_encode($temp_arr);
            }
        
        $temp_tetrimino=$this->tetromino->get();
        $this->board->draw();
        $this->tetromino->draw();

        $is_collision = $this->collisionCheck();
        if ($is_collision) {
                $this->tetromino->set($temp_tetrimino);
                $this->freezTetromino();
                $this->tetromino->reset();
   
        }
        $this->screenDisplay();
        
    }

    public function move($key){
        $temp_tetrimino=$this->tetromino->get();

        switch ($key) {
            //w
            case 'w':
                $this->tetromino->rotate();
                break;
              // s
              case 's':
                $this->tetromino->move('down');
                break;

            // a
            case 'a':
                $this->tetromino->move('left');
                break;

            // d
            case 'd':
                $this->tetromino->move('right');
                break;
        }
        $is_collision = $this->collisionCheck();
        if ($is_collision) {
           
            $this->tetromino->set($temp_tetrimino);
            $this->freezTetromino();
            $this->tetromino->reset();
            
            
        }

        
    }

    
    private function collisionCheck()
    {
        foreach ($this->tetromino->tilesPosPoint() as list($x, $y)) {
            if (!$this->board->validatePoint($x, $y)) {
                return true;
            }

            if ($this->board->existTile($x, $y)) {
                return true;
            }
        }

        return false;
    }

}
function main ()
{
    $tetris = new Tetris();

    if(isset($_POST['tetromino'])&&$_POST['tetromino']!=null)
            $tetris->setTetromino($_POST['tetromino']);

    if(isset($_POST['board'])&&$_POST['board']!=null)
            $tetris->setBoard($_POST['board']);

    if(isset($_POST['key'])&&$_POST['key']!=null){
            $p_key = $_POST['key'];
            $tetris->move($p_key);
    }
    $tetris->run();
}

main();
?>