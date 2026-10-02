import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:encrypt/encrypt.dart' as enc;
import 'package:flutter/foundation.dart';

class InfinityfreeBypasser {
  final Dio dio = Dio(BaseOptions(
    headers: {
      'User-Agent':
          'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36',
      'Accept':
          'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
      'Accept-Language': 'pt-BR,pt;q=0.9,en;q=0.8',
    },
    validateStatus: (s) => s != null && s < 500,
  ));

  Map<String, String> variables = {};
  String? cookie;

  Future<void> bypass(String baseUrl) async {
    if (cookie != null) return;
    await _calculateCookie(baseUrl);
  }

  Future<void> _calculateCookie(String baseUrl) async {
    try {
      debugPrint('[BYPASS] GET $baseUrl');
      final response = await dio.get(baseUrl);
      final body = response.data.toString();

      debugPrint('[BYPASS] status=${response.statusCode} len=${body.length}');
      debugPrint(
          '[BYPASS] preview: ${body.substring(0, body.length > 200 ? 200 : body.length)}');

      final scriptRegExp =
          RegExp(r'<script[^>]*>(.*?)</script>', dotAll: true);
      if (scriptRegExp.firstMatch(body) == null) {
        debugPrint('[BYPASS] nenhum <script> encontrado — sem challenge');
        return;
      }

      for (final varName in ['a', 'b', 'c']) {
        final varRegExp = RegExp('$varName=toNumbers\\("([0-9a-fA-F]+)"\\)');
        final varMatch = varRegExp.firstMatch(body);
        if (varMatch != null) {
          variables[varName] = varMatch.group(1)!;
        }
      }

      debugPrint('[BYPASS] vars=$variables');

      _generateByPassCookie();
    } catch (e) {
      debugPrint('[BYPASS] erro: $e');
    }
  }

  void _generateByPassCookie() {
    if (!variables.containsKey('a') ||
        !variables.containsKey('b') ||
        !variables.containsKey('c')) {
      debugPrint('[BYPASS] faltam vars, abortando');
      return;
    }

    final a = _toNumbers(variables['a']!);
    final b = _toNumbers(variables['b']!);
    final c = _toNumbers(variables['c']!);

    final key = enc.Key(Uint8List.fromList(a));
    final iv = enc.IV(Uint8List.fromList(b));
    final encrypter = enc.Encrypter(
      enc.AES(key, mode: enc.AESMode.cbc, padding: null),
    );

    final decrypted = encrypter.decryptBytes(
      enc.Encrypted(Uint8List.fromList(c)),
      iv: iv,
    );

    final cookieValue = _toHex(decrypted).toLowerCase();
    cookie = '__test=$cookieValue';
    debugPrint('[BYPASS] cookie gerado: $cookie');
  }

  List<int> _toNumbers(String hex) {
    final result = <int>[];
    for (var i = 0; i < hex.length; i += 2) {
      result.add(int.parse(hex.substring(i, i + 2), radix: 16));
    }
    return result;
  }

  String _toHex(List<int> bytes) {
    return bytes.map((b) => b.toRadixString(16).padLeft(2, '0')).join();
  }
}
