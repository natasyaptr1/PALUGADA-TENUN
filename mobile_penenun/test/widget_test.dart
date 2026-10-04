import 'package:flutter_test/flutter_test.dart';
import 'package:palugada_penenun/main.dart';

void main() {
  testWidgets('Aplikasi Palugada Penenun dapat dibangun', (tester) async {
    await tester.pumpWidget(const PalugadaPenenunApp());
    expect(find.text('Palugada Penenun'), findsOneWidget);
  });
}
