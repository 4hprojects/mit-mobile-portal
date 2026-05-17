import { StatusBar } from 'expo-status-bar';
import * as SecureStore from 'expo-secure-store';
import { useEffect, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  KeyboardAvoidingView,
  Platform,
  Pressable,
  SafeAreaView,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { WebView } from 'react-native-webview';

const API_BASE_URL = process.env.EXPO_PUBLIC_MOBILE_AUTH_API_URL || 'http://192.168.1.12:8000';
const TOKEN_KEY = 'mobile_portal_token';

export default function App() {
  const [token, setToken] = useState(null);
  const [user, setUser] = useState(null);
  const [login, setLogin] = useState('admin@leavemgmt.com');
  const [password, setPassword] = useState('password');
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [screen, setScreen] = useState('dashboard');
  const [webTitle, setWebTitle] = useState('');
  const [webUrl, setWebUrl] = useState(null);

  const headers = useMemo(() => ({
    Accept: 'application/json',
    'Content-Type': 'application/json',
  }), []);

  useEffect(() => {
    restoreSession();
  }, []);

  async function request(path, options = {}) {
    const response = await fetch(`${API_BASE_URL}${path}`, {
      ...options,
      headers: {
        ...headers,
        ...(options.token ? { Authorization: `Bearer ${options.token}` } : {}),
        ...(options.headers || {}),
      },
    });

    const data = await response.json().catch(() => ({}));

    if (!response.ok || data.success === false) {
      const message = data?.error?.message || data?.message || 'Request failed.';
      throw new Error(message);
    }

    return data;
  }

  async function restoreSession() {
    try {
      const storedToken = await SecureStore.getItemAsync(TOKEN_KEY);

      if (!storedToken) {
        return;
      }

      const data = await request('/api/mobile/me', {
        method: 'GET',
        token: storedToken,
      });

      setToken(storedToken);
      setUser(data.user);
    } catch {
      await SecureStore.deleteItemAsync(TOKEN_KEY);
      setToken(null);
      setUser(null);
    } finally {
      setLoading(false);
    }
  }

  async function handleLogin() {
    setSubmitting(true);

    try {
      const data = await request('/api/mobile/login', {
        method: 'POST',
        body: JSON.stringify({ login, password }),
      });

      await SecureStore.setItemAsync(TOKEN_KEY, data.token);
      setToken(data.token);
      setUser(data.user);
      setScreen('dashboard');
    } catch (error) {
      Alert.alert('Login Failed', error.message);
    } finally {
      setSubmitting(false);
    }
  }

  async function handleLogout() {
    try {
      if (token) {
        await request('/api/mobile/logout', {
          method: 'POST',
          token,
        });
      }
    } catch {
      // Local session cleanup is still required even if server logout fails.
    }

    await SecureStore.deleteItemAsync(TOKEN_KEY);
    setToken(null);
    setUser(null);
    setWebUrl(null);
    setScreen('dashboard');
  }

  async function openSystem(system) {
    if (system === 'medical') {
      Alert.alert('Medical Management', 'Medical Management is not enabled yet.');
      return;
    }

    setSubmitting(true);

    try {
      const data = await request('/api/mobile/token/leave', {
        method: 'POST',
        token,
      });

      setWebTitle('Leave Management');
      setWebUrl(data.loginUrl);
      setScreen('webview');
    } catch (error) {
      Alert.alert('Access Failed', error.message);
    } finally {
      setSubmitting(false);
    }
  }

  if (loading) {
    return (
      <SafeAreaView style={styles.centered}>
        <ActivityIndicator size="large" color="#155e75" />
        <StatusBar style="dark" />
      </SafeAreaView>
    );
  }

  if (!token || !user) {
    return (
      <SafeAreaView style={styles.safeArea}>
        <KeyboardAvoidingView
          behavior={Platform.OS === 'ios' ? 'padding' : undefined}
          style={styles.keyboardView}
        >
          <ScrollView contentContainerStyle={styles.loginContent} keyboardShouldPersistTaps="handled">
            <View style={styles.brandBlock}>
              <Text style={styles.brandLabel}>MIT Mobile Portal</Text>
              <Text style={styles.loginTitle}>Sign in</Text>
            </View>

            <View style={styles.form}>
              <Text style={styles.inputLabel}>Email or username</Text>
              <TextInput
                autoCapitalize="none"
                autoCorrect={false}
                keyboardType="email-address"
                onChangeText={setLogin}
                placeholder="admin@leavemgmt.com"
                style={styles.input}
                value={login}
              />

              <Text style={styles.inputLabel}>Password</Text>
              <TextInput
                onChangeText={setPassword}
                placeholder="Password"
                secureTextEntry
                style={styles.input}
                value={password}
              />

              <Pressable
                disabled={submitting}
                onPress={handleLogin}
                style={({ pressed }) => [
                  styles.primaryButton,
                  pressed && styles.pressed,
                  submitting && styles.disabled,
                ]}
              >
                {submitting ? (
                  <ActivityIndicator color="#fff" />
                ) : (
                  <Text style={styles.primaryButtonText}>Sign in</Text>
                )}
              </Pressable>
            </View>
          </ScrollView>
        </KeyboardAvoidingView>
        <StatusBar style="dark" />
      </SafeAreaView>
    );
  }

  if (screen === 'webview' && webUrl) {
    return (
      <SafeAreaView style={styles.webContainer}>
        <View style={styles.webHeader}>
          <Pressable onPress={() => setScreen('dashboard')} style={styles.headerButton}>
            <Text style={styles.headerButtonText}>Back</Text>
          </Pressable>
          <Text numberOfLines={1} style={styles.webTitle}>{webTitle}</Text>
          <View style={styles.headerSpacer} />
        </View>
        <WebView
          source={{ uri: webUrl }}
          sharedCookiesEnabled
          thirdPartyCookiesEnabled
          startInLoadingState
          renderLoading={() => (
            <View style={styles.webLoading}>
              <ActivityIndicator size="large" color="#155e75" />
            </View>
          )}
        />
        <StatusBar style="dark" />
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={styles.safeArea}>
      <ScrollView contentContainerStyle={styles.dashboardContent}>
        <View style={styles.topBar}>
          <View>
            <Text style={styles.brandLabel}>MIT Mobile Portal</Text>
            <Text style={styles.userName}>{user.name}</Text>
          </View>
          <Pressable onPress={handleLogout} style={styles.logoutButton}>
            <Text style={styles.logoutButtonText}>Logout</Text>
          </Pressable>
        </View>

        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Systems</Text>

          <Pressable
            disabled={!user.access?.leave || submitting}
            onPress={() => openSystem('leave')}
            style={({ pressed }) => [
              styles.systemButton,
              pressed && styles.pressed,
              (!user.access?.leave || submitting) && styles.disabledSystem,
            ]}
          >
            <View>
              <Text style={styles.systemTitle}>Leave Management</Text>
              <Text style={styles.systemMeta}>Available</Text>
            </View>
            <Text style={styles.systemArrow}>Open</Text>
          </Pressable>

          <Pressable
            disabled
            onPress={() => openSystem('medical')}
            style={[styles.systemButton, styles.disabledSystem]}
          >
            <View>
              <Text style={styles.systemTitle}>Medical Management</Text>
              <Text style={styles.systemMeta}>Pending integration</Text>
            </View>
            <Text style={styles.systemArrow}>Disabled</Text>
          </Pressable>
        </View>
      </ScrollView>
      <StatusBar style="dark" />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: '#f4f7f7',
  },
  keyboardView: {
    flex: 1,
  },
  centered: {
    alignItems: 'center',
    backgroundColor: '#f4f7f7',
    flex: 1,
    justifyContent: 'center',
  },
  loginContent: {
    flexGrow: 1,
    justifyContent: 'center',
    padding: 24,
  },
  brandBlock: {
    marginBottom: 28,
  },
  brandLabel: {
    color: '#155e75',
    fontSize: 14,
    fontWeight: '700',
  },
  loginTitle: {
    color: '#102a2f',
    fontSize: 36,
    fontWeight: '800',
    marginTop: 8,
  },
  form: {
    gap: 10,
  },
  inputLabel: {
    color: '#31474d',
    fontSize: 13,
    fontWeight: '700',
    marginTop: 10,
  },
  input: {
    backgroundColor: '#fff',
    borderColor: '#c9d7d9',
    borderRadius: 8,
    borderWidth: 1,
    color: '#102a2f',
    fontSize: 16,
    minHeight: 52,
    paddingHorizontal: 14,
  },
  primaryButton: {
    alignItems: 'center',
    backgroundColor: '#155e75',
    borderRadius: 8,
    justifyContent: 'center',
    marginTop: 18,
    minHeight: 52,
  },
  primaryButtonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '800',
  },
  pressed: {
    opacity: 0.76,
  },
  disabled: {
    opacity: 0.65,
  },
  dashboardContent: {
    padding: 20,
  },
  topBar: {
    alignItems: 'center',
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 28,
  },
  userName: {
    color: '#102a2f',
    fontSize: 24,
    fontWeight: '800',
    marginTop: 4,
  },
  logoutButton: {
    borderColor: '#b8c9cb',
    borderRadius: 8,
    borderWidth: 1,
    paddingHorizontal: 14,
    paddingVertical: 10,
  },
  logoutButtonText: {
    color: '#31474d',
    fontWeight: '800',
  },
  section: {
    gap: 12,
  },
  sectionTitle: {
    color: '#31474d',
    fontSize: 13,
    fontWeight: '800',
    textTransform: 'uppercase',
  },
  systemButton: {
    alignItems: 'center',
    backgroundColor: '#fff',
    borderColor: '#d6e0e1',
    borderRadius: 8,
    borderWidth: 1,
    flexDirection: 'row',
    justifyContent: 'space-between',
    minHeight: 78,
    padding: 16,
  },
  disabledSystem: {
    opacity: 0.55,
  },
  systemTitle: {
    color: '#102a2f',
    fontSize: 17,
    fontWeight: '800',
  },
  systemMeta: {
    color: '#637b80',
    fontSize: 13,
    marginTop: 4,
  },
  systemArrow: {
    color: '#155e75',
    fontSize: 13,
    fontWeight: '800',
  },
  webContainer: {
    backgroundColor: '#fff',
    flex: 1,
  },
  webHeader: {
    alignItems: 'center',
    borderBottomColor: '#d6e0e1',
    borderBottomWidth: 1,
    flexDirection: 'row',
    minHeight: 52,
    paddingHorizontal: 12,
  },
  headerButton: {
    paddingHorizontal: 8,
    paddingVertical: 8,
    width: 72,
  },
  headerButtonText: {
    color: '#155e75',
    fontWeight: '800',
  },
  webTitle: {
    color: '#102a2f',
    flex: 1,
    fontSize: 16,
    fontWeight: '800',
    textAlign: 'center',
  },
  headerSpacer: {
    width: 72,
  },
  webLoading: {
    ...StyleSheet.absoluteFillObject,
    alignItems: 'center',
    backgroundColor: '#fff',
    justifyContent: 'center',
  },
});
