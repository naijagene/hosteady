import type { AxiosError } from 'axios'
import { apiClient } from '../client'
import { ApiError } from '../errors'
import { unwrapData } from '../unwrap'
import type { ApiErrorBody } from '../types/api'
import { asArray } from '../types/metadata-common'
import {
  buildNotificationQueryRequest,
  normalizeAnnouncement,
  normalizeMention,
  normalizeNotification,
  normalizeReminder,
  type Announcement,
  type Mention,
  type Notification,
  type NotificationQueryPayload,
  type Reminder,
} from '../types/notifications'
import type { NotificationSummaryResponse } from '../types/runtime'

export async function fetchNotifications(payload: NotificationQueryPayload = {}): Promise<Notification[]> {
  try {
    const response = await apiClient.get<
      Notification[] | { data: Notification[] } | { data: unknown[] }
    >('tenant/notifications', { params: buildNotificationQueryRequest(payload) })

    return asArray(unwrapData(response.data)).map(normalizeNotification)
  } catch (error) {
    throw ApiError.fromAxios(error as AxiosError<ApiErrorBody>)
  }
}

export async function fetchUnreadNotifications(limit = 50): Promise<NotificationSummaryResponse[]> {
  const notifications = await fetchNotifications({ per_page: limit, unread_only: true })
  return notifications.map((notification) => ({
    public_id: notification.public_id,
    title: notification.title,
    read_at: notification.read_at ?? null,
  }))
}

export async function fetchUnreadNotificationCount(): Promise<number> {
  try {
    const notifications = await fetchNotifications({ per_page: 100 })
    return notifications.filter((notification) => !notification.read_at).length
  } catch {
    return 0
  }
}

export async function fetchNotification(publicId: string): Promise<Notification> {
  try {
    const response = await apiClient.get<Notification | { data: Notification }>(
      `tenant/notifications/${encodeURIComponent(publicId)}`,
    )
    return normalizeNotification(unwrapData(response.data))
  } catch (error) {
    throw ApiError.fromAxios(error as AxiosError<ApiErrorBody>)
  }
}

async function patchNotificationReadState(publicId: string, action: 'read' | 'unread'): Promise<Notification> {
  const path = `tenant/notifications/${encodeURIComponent(publicId)}/${action}`
  try {
    const response = await apiClient.patch<Notification | { data: Notification }>(path)
    return normalizeNotification(unwrapData(response.data))
  } catch {
    try {
      const response = await apiClient.post<Notification | { data: Notification }>(path)
      return normalizeNotification(unwrapData(response.data))
    } catch (postError) {
      throw ApiError.fromAxios(postError as AxiosError<ApiErrorBody>)
    }
  }
}

export async function markNotificationRead(publicId: string): Promise<Notification> {
  return patchNotificationReadState(publicId, 'read')
}

export async function markNotificationUnread(publicId: string): Promise<Notification> {
  return patchNotificationReadState(publicId, 'unread')
}

export async function markAllNotificationsRead(): Promise<{ updated?: number }> {
  try {
    const response = await apiClient.post<{ updated?: number } | { data: { updated?: number } }>(
      'tenant/notifications/read-all',
    )
    return unwrapData(response.data) as { updated?: number }
  } catch (error) {
    throw ApiError.fromAxios(error as AxiosError<ApiErrorBody>)
  }
}

export async function deleteNotification(publicId: string): Promise<Notification> {
  try {
    const response = await apiClient.delete<Notification | { data: Notification }>(
      `tenant/notifications/${encodeURIComponent(publicId)}`,
    )
    return normalizeNotification(unwrapData(response.data))
  } catch (error) {
    throw ApiError.fromAxios(error as AxiosError<ApiErrorBody>)
  }
}

export async function fetchAnnouncements(payload: NotificationQueryPayload = {}): Promise<Announcement[]> {
  try {
    const response = await apiClient.get<Announcement[] | { data: Announcement[] } | { data: unknown[] }>(
      'tenant/announcements',
      { params: buildNotificationQueryRequest(payload) },
    )
    return asArray(unwrapData(response.data)).map(normalizeAnnouncement)
  } catch {
    const notifications = await fetchNotifications(payload)
    return notifications
      .filter((notification) => notification.category === 'announcement')
      .map((notification) => normalizeAnnouncement(notification))
  }
}

export async function fetchReminders(payload: NotificationQueryPayload = {}): Promise<Reminder[]> {
  try {
    const response = await apiClient.get<Reminder[] | { data: Reminder[] } | { data: unknown[] }>(
      'tenant/reminders',
      { params: buildNotificationQueryRequest(payload) },
    )
    return asArray(unwrapData(response.data)).map(normalizeReminder)
  } catch {
    const notifications = await fetchNotifications(payload)
    return notifications
      .filter((notification) => notification.category === 'reminder')
      .map((notification) => normalizeReminder(notification))
  }
}

export async function fetchMentions(payload: NotificationQueryPayload = {}): Promise<Mention[]> {
  try {
    const response = await apiClient.get<Mention[] | { data: Mention[] } | { data: unknown[] }>(
      'tenant/mentions',
      { params: buildNotificationQueryRequest(payload) },
    )
    return asArray(unwrapData(response.data)).map(normalizeMention)
  } catch {
    const notifications = await fetchNotifications(payload)
    return notifications
      .filter((notification) => notification.category === 'mention')
      .map((notification) => normalizeMention(notification))
  }
}
