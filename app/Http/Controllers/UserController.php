<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Response;

class UserController extends Controller
{
    // Se eliminan las propiedades hardcodeadas
    private $token;
    private $domainname;

    public function __construct()
    {
        // Asignar las variables desde el archivo .env
        $this->token = config('services.moodle.token');
        $this->domainname = config('services.moodle.endpoint');
    }

    public function index()
    {
        $users = User::all();
        return view('usuarios.index', compact('users'));
    }

    public function create()
    {
        return view('usuarios.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required',
            'ap_paterno'  => 'required',
            'ap_materno'  => 'required',
            'email'       => 'required|email|unique:users,email',
            'cedula'      => 'required|unique:users,cedula',
            'celular'     => 'required',
        ]);

        try {
            $primeraLetra = strtolower(substr($request->name, 0, 1));
            $password = 'P' . $primeraLetra . $request->cedula . '*';

            $response = Http::asForm()->post($this->domainname, [
                'wstoken' => $this->token,
                'wsfunction' => 'core_user_create_users',
                'moodlewsrestformat' => 'json',
                'users[0][username]' => $request->cedula,
                'users[0][password]' => $password,
                'users[0][firstname]' => $request->name,
                'users[0][lastname]' => $request->ap_paterno . ' ' . $request->ap_materno,
                'users[0][email]' => $request->email,
                'users[0][phone1]' => $request->celular,
                'users[0][country]' => 'EC',
                'users[0][auth]' => 'manual',
            ]);

            $data = $response->json();
            Log::info('Respuesta de Moodle al crear usuario:', $data);

            if (!is_array($data) || isset($data['exception'])) {
                return back()->with('error', 'Error en Moodle: ' . ($data['message'] ?? 'Respuesta inválida.'));
            }

            $idMoodle = $data[0]['id'] ?? null;
            if (!$idMoodle) {
                return back()->with('error', 'No se recibió un ID válido desde Moodle.');
            }

            $user = new User();
            $user->name = $request->name;
            $user->ap_paterno = $request->ap_paterno;
            $user->ap_materno = $request->ap_materno;
            $user->cedula = $request->cedula;
            $user->email = $request->email;
            $user->celular = $request->celular;
            $user->id_user_moodle = $idMoodle;
            $user->password = Hash::make($request->cedula);

            $user->save();

            return redirect()->route('users.index')->with([
                'info' => 'El usuario fue creado correctamente.',
                'password_temporal' => $password
            ]);

        } catch (\Exception $e) {
            Log::error('Error al crear usuario: ' . $e->getMessage());
            return back()->with('error', 'Error inesperado. Revisa los logs.');
        }
    }

    public function edit(User $user)
    {
        return view('usuarios.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required',
            'ap_paterno' => 'required',
            'ap_materno' => 'required',
            'email' => 'required|unique:users,email,' . $user->id,
            'cedula' => 'required|unique:users,cedula,' . $user->id,
            'celular' => 'required',
        ]);

        $response = Http::asForm()->post($this->domainname, [
            'wstoken' => $this->token,
            'wsfunction' => 'core_user_update_users',
            'moodlewsrestformat' => 'json',
            'users[0][id]' => $user->id_user_moodle,
            'users[0][firstname]' => $request->name,
            'users[0][lastname]' => $request->ap_paterno . ' ' . $request->ap_materno,
            'users[0][email]' => $request->email,
            'users[0][phone1]' => $request->celular,
        ]);

        $data = $response->json();

        if (isset($data['exception'])) {
            return back()->with('error', 'Error al actualizar en Moodle: ' . ($data['message'] ?? 'Error desconocido.'));
        }

        $user->name = $request->name;
        $user->ap_paterno = $request->ap_paterno;
        $user->ap_materno = $request->ap_materno;
        $user->cedula = $request->cedula;
        $user->email = $request->email;
        $user->celular = $request->celular;
        $user->save();

        return redirect()->route('users.index')->with('info', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $user)
    {
        $response = Http::asForm()->post($this->domainname, [
            'wstoken' => $this->token,
            'wsfunction' => 'core_user_delete_users',
            'moodlewsrestformat' => 'json',
            'userids[0]' => $user->id_user_moodle,
        ]);

        $data = $response->json();

        if (isset($data['exception'])) {
            return back()->with('error', 'Error al eliminar en Moodle: ' . ($data['message'] ?? 'Error desconocido.'));
        }

        $user->delete();

        return redirect()->route('users.index')->with('info', 'Usuario eliminado correctamente.');
    }

    public function formularioCsv()
    {
        return view('usuarios.csv');
    }

    public function importarCsv(Request $request)
    {
        $request->validate([
            'archivo_csv' => 'required|file|mimes:csv,txt'
        ]);

        $file = $request->file('archivo_csv');
        $handle = fopen($file, 'r');
        $header = fgetcsv($handle); // Leer encabezado

        $totalUsuarios = 0;
        $totalMatriculas = 0;

        try {
            while (($data = fgetcsv($handle)) !== false) {
                if (count($data) < 8) {
                    Log::error('Fila incompleta en CSV', ['linea' => $data]);
                    continue;
                }

                [$nombre, $ap_paterno, $ap_materno, $cedula, $email, $celular, $grado, $periodo] = array_map('trim', $data);

                if (
                    empty($nombre) || empty($ap_paterno) || empty($ap_materno) ||
                    empty($cedula) || empty($email) || empty($celular) ||
                    empty($grado) || empty($periodo)
                ) {
                    Log::warning("Fila con campos vacíos: ", $data);
                    continue;
                }

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    Log::warning("Email inválido en CSV: " . $email);
                    continue;
                }

                if (User::where('email', $email)->exists() || User::where('cedula', $cedula)->exists()) {
                    continue;
                }

                $primeraLetra = strtolower(substr($nombre, 0, 1));
                $password = 'P' . $primeraLetra . $cedula . '*';

                // Crear usuario en Moodle
                $moodleResponse = Http::asForm()->post($this->domainname, [
                    'wstoken' => $this->token,
                    'wsfunction' => 'core_user_create_users',
                    'moodlewsrestformat' => 'json',
                    'users[0][username]' => $cedula,
                    'users[0][password]' => $password,
                    'users[0][firstname]' => $nombre,
                    'users[0][lastname]' => $ap_paterno . ' ' . $ap_materno,
                    'users[0][email]' => $email,
                    'users[0][phone1]' => $celular,
                    'users[0][country]' => 'EC',
                    'users[0][auth]' => 'manual',
                ]);

                $moodleData = $moodleResponse->json();

                if (!is_array($moodleData) || isset($moodleData['exception'])) {
                    Log::error('Error creando usuario en Moodle: ' . json_encode($moodleData));
                    continue;
                }

                $idMoodle = isset($moodleData[0]['id']) ? (int) $moodleData[0]['id'] : null;
                if (!$idMoodle) continue;

                // Crear usuario en Laravel
                User::create([
                    'name' => $nombre,
                    'ap_paterno' => $ap_paterno,
                    'ap_materno' => $ap_materno,
                    'cedula' => $cedula,
                    'email' => $email,
                    'celular' => $celular,
                    'grado' => $grado,
                    'periodo' => $periodo,
                    'id_user_moodle' => $idMoodle,
                    'password' => Hash::make($cedula),
                ]);

                $totalUsuarios++;

                // Obtener cursos desde Moodle
                $cursosResponse = Http::asForm()->post($this->domainname, [
                    'wstoken' => $this->token,
                    'wsfunction' => 'core_course_get_courses',
                    'moodlewsrestformat' => 'json',
                ]);

                $todosLosCursos = $cursosResponse->json();

                if (!is_array($todosLosCursos)) {
                    Log::error('Respuesta inesperada de Moodle al obtener cursos:', $todosLosCursos);
                    continue;
                }

                // Matricular en cursos que coincidan con grado y periodo
                foreach ($todosLosCursos as $curso) {
                    if (
                        isset($curso['shortname']) &&
                        stripos($curso['shortname'], $grado) !== false &&
                        stripos($curso['shortname'], $periodo) !== false
                    ) {
                        Http::asForm()->post($this->domainname, [
                            'wstoken' => $this->token,
                            'wsfunction' => 'enrol_manual_enrol_users',
                            'moodlewsrestformat' => 'json',
                            'enrolments[0][roleid]' => 5,
                            'enrolments[0][userid]' => $idMoodle,
                            'enrolments[0][courseid]' => $curso['id'],
                        ]);

                        $totalMatriculas++;
                    }
                }
            }

            fclose($handle);

            return back()->with('success', "CSV procesado correctamente. Se crearon $totalUsuarios usuarios y se realizaron $totalMatriculas matrículas.");
        } catch (\Exception $e) {
            Log::error('Error al procesar CSV: ' . $e->getMessage());
            return back()->with('error', 'Error inesperado al procesar el archivo.');
        }
    }


    public function exportarCsv()
    {
        $usuarios = User::all(['name', 'ap_paterno', 'ap_materno', 'email', 'cedula', 'celular']);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="usuarios_exportados.csv"',
        ];

        $callback = function () use ($usuarios) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['nombre', 'ap_paterno', 'ap_materno', 'email', 'cedula', 'celular']);

            foreach ($usuarios as $usuario) {
                fputcsv($file, [
                    $usuario->name,
                    $usuario->ap_paterno,
                    $usuario->ap_materno,
                    $usuario->email,
                    $usuario->cedula,
                    $usuario->celular,
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }
}
