import 'package:flutter/material.dart';
import 'package:dio/dio.dart';
import 'package:cookie_jar/cookie_jar.dart';
import 'package:dio_cookie_manager/dio_cookie_manager.dart';

import 'infinityfree_bypasser.dart';

const String kBaseUrl = 'https://cata-junto.wuaze.com';
const String kUserAgent =
    'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36';

final cookieJar = CookieJar();
final bypasser = InfinityfreeBypasser();

final dio = Dio(BaseOptions(
  baseUrl: kBaseUrl,
  headers: {
    'Content-Type': 'application/json',
    'User-Agent': kUserAgent,
    'Accept': 'application/json, text/plain, */*',
    'Accept-Language': 'pt-BR,pt;q=0.9,en;q=0.8',
    'Origin': kBaseUrl,
    'Referer': '$kBaseUrl/',
  },
  connectTimeout: const Duration(seconds: 60),
  receiveTimeout: const Duration(seconds: 60),
  sendTimeout: const Duration(seconds: 60),
  validateStatus: (s) => s != null && s < 500,
));

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  dio.interceptors.add(CookieManager(cookieJar));
  dio.interceptors.add(InterceptorsWrapper(
    onRequest: (options, handler) {
      if (bypasser.cookie != null) {
        final existing = options.headers['Cookie'] as String?;
        options.headers['Cookie'] = existing == null
            ? bypasser.cookie!
            : '${bypasser.cookie!}; $existing';
      }
      handler.next(options);
    },
  ));

  await bypasser.bypass('$kBaseUrl/person/me');

  runApp(const App());
}

class App extends StatelessWidget {
  const App({super.key});

  @override
  Widget build(BuildContext context) {
    return const MaterialApp(
      debugShowCheckedModeBanner: false,
      home: LoginScreen(),
    );
  }
}

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _email = TextEditingController();
  final _password = TextEditingController();
  bool _loading = false;
  String _error = '';

  @override
  void dispose() {
    _email.dispose();
    _password.dispose();
    super.dispose();
  }

  Future<void> _login() async {
    setState(() {
      _loading = true;
      _error = '';
    });

    try {
      final res = await dio.post('/login/person', data: {
        'email': _email.text.trim(),
        'password': _password.text,
      });

      final data = res.data;

      if (data is String && data.contains('<script')) {
        setState(() => _error = 'Servidor inacessível. Reabra o app.');
        return;
      }

      if (res.statusCode == 200) {
        if (!mounted) return;
        Navigator.pushReplacement(
          context,
          MaterialPageRoute(builder: (_) => const CollectionsScreen()),
        );
        return;
      }

      if (res.statusCode == 401) {
        setState(() => _error = 'E-mail ou senha inválidos.');
        return;
      }

      if (res.statusCode == 403) {
        setState(() => _error = 'Conta inativa.');
        return;
      }

      setState(() => _error = 'Falha no login (${res.statusCode}).');
    } on DioException catch (e) {
      if (e.type == DioExceptionType.connectionTimeout ||
          e.type == DioExceptionType.receiveTimeout ||
          e.type == DioExceptionType.sendTimeout) {
        setState(() => _error = 'O servidor demorou para responder.');
      } else if (e.type == DioExceptionType.connectionError) {
        setState(() => _error = 'Sem conexão com o servidor.');
      } else {
        setState(() => _error = 'Erro de rede.');
      }
    } catch (_) {
      setState(() => _error = 'Erro inesperado.');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Cata-Junto')),
      body: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            TextField(
              controller: _email,
              keyboardType: TextInputType.emailAddress,
              decoration: const InputDecoration(
                labelText: 'E-mail',
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 16),
            TextField(
              controller: _password,
              obscureText: true,
              decoration: const InputDecoration(
                labelText: 'Senha',
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 20),
            if (_error.isNotEmpty)
              Padding(
                padding: const EdgeInsets.only(bottom: 12),
                child: Text(
                  _error,
                  style: const TextStyle(color: Colors.red),
                  textAlign: TextAlign.center,
                ),
              ),
            SizedBox(
              width: double.infinity,
              child: _loading
                  ? const Center(child: CircularProgressIndicator())
                  : ElevatedButton(
                      onPressed: _login,
                      child: const Text('Entrar'),
                    ),
            ),
          ],
        ),
      ),
    );
  }
}

class CollectionsScreen extends StatefulWidget {
  const CollectionsScreen({super.key});

  @override
  State<CollectionsScreen> createState() => _CollectionsScreenState();
}

class _CollectionsScreenState extends State<CollectionsScreen> {
  bool _loading = true;
  String _error = '';
  List _collections = [];

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = '';
    });

    try {
      final res = await dio.get('/available-residential-collections');
      final data = res.data;

      if (data is String && data.contains('<script')) {
        setState(() => _error = 'Servidor inacessível. Reabra o app.');
        return;
      }

      if (res.statusCode == 200 && data is List) {
        setState(() {
          _collections =
              data.where((c) => c['status'] == 'pending').toList();
        });
        return;
      }

      if (res.statusCode == 401) {
        if (!mounted) return;
        Navigator.pushReplacement(
          context,
          MaterialPageRoute(builder: (_) => const LoginScreen()),
        );
        return;
      }

      setState(() => _error = 'Falha ao carregar (${res.statusCode}).');
    } on DioException catch (e) {
      if (e.type == DioExceptionType.connectionTimeout ||
          e.type == DioExceptionType.receiveTimeout) {
        setState(() => _error = 'O servidor demorou para responder.');
      } else {
        setState(() => _error = 'Erro de rede.');
      }
    } catch (_) {
      setState(() => _error = 'Erro inesperado.');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _logout() async {
    try {
      await dio.post('/logout');
    } catch (_) {}
    if (!mounted) return;
    Navigator.pushReplacement(
      context,
      MaterialPageRoute(builder: (_) => const LoginScreen()),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Coletas Pendentes'),
        actions: [
          IconButton(
            icon: const Icon(Icons.logout),
            tooltip: 'Sair',
            onPressed: _logout,
          ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error.isNotEmpty
              ? Center(
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(_error, textAlign: TextAlign.center),
                        const SizedBox(height: 16),
                        ElevatedButton(
                          onPressed: _load,
                          child: const Text('Tentar de novo'),
                        ),
                      ],
                    ),
                  ),
                )
              : _collections.isEmpty
                  ? const Center(child: Text('Nenhuma coleta pendente.'))
                  : RefreshIndicator(
                      onRefresh: _load,
                      child: ListView.builder(
                        padding: const EdgeInsets.all(12),
                        itemCount: _collections.length,
                        itemBuilder: (context, i) {
                          final c = _collections[i];
                          return Card(
                            child: ListTile(
                              title: Text('Material #${c['material_type_id']}'),
                              subtitle: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text('Tipo: ${c['collect_type'] ?? '-'}'),
                                  Text('Descrição: ${c['description'] ?? '-'}'),
                                  Text('Solicitado em: ${c['requested_at'] ?? '-'}'),
                                ],
                              ),
                              isThreeLine: true,
                            ),
                          );
                        },
                      ),
                    ),
    );
  }
}
